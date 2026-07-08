<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . '/../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../SECURE/tenant.php';
require_once __DIR__ . '/../SECURE/resendMail.php';

$tenant_id = getTenantId($conn);

// Fetch all tenant credentials from DB
$tenantStmt = $conn->prepare("SELECT flutterwave_secret_key, telegram_bot_token, telegram_chat_id, notification_email FROM tenants WHERE id = ?");
$tenantStmt->bind_param("i", $tenant_id);
$tenantStmt->execute();
$tenantRow   = $tenantStmt->get_result()->fetch_assoc();
$secretKey   = $tenantRow['flutterwave_secret_key'] ?? '';
$botToken    = $tenantRow['telegram_bot_token'] ?? '';
$chatId      = $tenantRow['telegram_chat_id'] ?? '';
$notifyEmail = $tenantRow['notification_email'] ?? '';

if (!$secretKey) {
    echo json_encode(["success" => false, "message" => "Secret key not found"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$tableId        = $data['tableId']        ?? null;
$name           = $data['name']           ?? null;
$email          = $data['email']          ?? null;
$phone          = $data['phone']          ?? null;
$bookingDate    = $data['bookingDate']    ?? null;
$transaction_id = $data['transaction_id'] ?? null;

if (!$tableId || !$name || !$email || !$phone || !$bookingDate || !$transaction_id) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Verify payment with Flutterwave

// Production: SSL verification enabled
// $curl = curl_init();
// curl_setopt_array($curl, [
//     CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
//     CURLOPT_RETURNTRANSFER => true,
//     CURLOPT_CUSTOMREQUEST  => "GET",
//     CURLOPT_HTTPHEADER     => [
//         "Authorization: Bearer $secretKey",
//         "Content-Type: application/json"
//     ],
//     CURLOPT_SSL_VERIFYPEER => true,
//     CURLOPT_SSL_VERIFYHOST => 2,
// ]);

// Local: SSL verification disabled (AWebServer has no SSL certs bundle)
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => "GET",
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (!$result || $result['status'] !== 'success' || $result['data']['status'] !== 'successful') {
    echo json_encode(["success" => false, "message" => "Payment not verified"]);
    exit;
}

$amount           = (float)$result['data']['amount'];
$bookingDate      = date("Y-m-d H:i:s", strtotime($bookingDate));
$reservation_code = "RES-ART-" . strtoupper(substr(md5(uniqid()), 0, 8));

// Mark table as booked
$stmt = $conn->prepare("
    INSERT INTO booked_tables (tenant_id, table_id, booked)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE booked = 1
");
$stmt->bind_param("ii", $tenant_id, $tableId);
$stmt->execute();

// Save reservation
$stmt2 = $conn->prepare("
    INSERT INTO reservations 
    (tenant_id, table_id, name, email, phone, booking_date, amount, transaction_id, status, reservation_code)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
");
$stmt2->bind_param(
    "iissssdss",
    $tenant_id,
    $tableId,
    $name,
    $email,
    $phone,
    $bookingDate,
    $amount,
    $transaction_id,
    $reservation_code
);
$stmt2->execute();

$reservation_id = $stmt2->insert_id;

if ($reservation_id) {

    $emailBody = "
    <h2>📅 New Table Reservation</h2>
    <p><b>Reservation Code:</b> $reservation_code</p>
    <p><b>Name:</b> $name</p>
    <p><b>Email:</b> $email</p>
    <p><b>Phone:</b> $phone</p>
    <p><b>Table ID:</b> $tableId</p>
    <p><b>Booking Date:</b> $bookingDate</p>
    <p><b>Amount Paid:</b> ₦$amount</p>
    <p><b>Transaction ID:</b> $transaction_id</p>
    ";

    sendEmail($notifyEmail, "New Table Reservation - $reservation_code", $emailBody);

    $message = "📅 *New Table Reservation\!*\n\n🎟️ *Code:* {$reservation_code}\n👤 *Name:* {$name}\n📧 *Email:* {$email}\n📞 *Phone:* {$phone}\n🪑 *Table ID:* {$tableId}\n📆 *Date:* {$bookingDate}\n💰 *Amount:* ₦{$amount}\n💳 *Transaction ID:* {$transaction_id}";

    $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["chat_id" => $chatId, "text" => $message, "parse_mode" => "MarkdownV2"]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        "success"          => true,
        "reservation_id"   => $reservation_id,
        "reservation_code" => $reservation_code
    ]);

} else {
    echo json_encode(["success" => false, "message" => "Booking failed"]);
}
