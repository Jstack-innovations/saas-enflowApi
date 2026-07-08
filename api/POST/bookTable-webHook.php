<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../SECURE/db.php';
require_once __DIR__ . '/../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$data = json_decode(file_get_contents("php://input"), true);

$tableId        = $data['tableId']        ?? null;
$name           = $data['name']           ?? null;
$email          = $data['email']          ?? null;
$phone          = $data['phone']          ?? null;
$bookingDate    = $data['bookingDate']    ?? null;
$amount         = $data['amount']         ?? null;
$transaction_id = $data['transaction_id'] ?? null;

if (!$tableId || !$name || !$email || !$phone || !$bookingDate || !$amount || !$transaction_id) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$keyStmt = $conn->prepare("SELECT flutterwave_secret_key FROM tenants WHERE id = ?");
$keyStmt->bind_param("i", $tenant_id);
$keyStmt->execute();
$secretKey = $keyStmt->get_result()->fetch_assoc()['flutterwave_secret_key'] ?? '';
$keyStmt->close();

if (!$secretKey) {
    echo json_encode(["success" => false, "message" => "Secret key not found"]);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secretKey"],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$response  = curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);

if ($curlError) {
    echo json_encode(["success" => false, "message" => "Payment gateway error"]);
    exit;
}

$result = json_decode($response, true);

if (
    !$result ||
    ($result['status'] ?? '') !== 'success' ||
    ($result['data']['status'] ?? '') !== 'successful'
) {
    echo json_encode(["success" => false, "message" => "Payment not verified"]);
    exit;
}

// Check if webhook already confirmed it
$chkStmt = $conn->prepare("SELECT id, status FROM reservations WHERE transaction_id = ? AND tenant_id = ? LIMIT 1");
$chkStmt->bind_param("si", $transaction_id, $tenant_id);
$chkStmt->execute();
$existing = $chkStmt->get_result()->fetch_assoc();
$chkStmt->close();

if ($existing) {
    echo json_encode([
        "success"          => true,
        "reservation_id"   => $existing['id'],
        "reservation_code" => null
    ]);
    exit;
}

$bookingDate      = date("Y-m-d H:i:s", strtotime($bookingDate));
$reservation_code = "RES-ART-" . strtoupper(substr(md5(uniqid()), 0, 8));

$stmt = $conn->prepare("
    INSERT INTO reservations 
    (tenant_id, table_id, name, email, phone, booking_date, amount, transaction_id, status, reservation_code)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
");
$stmt->bind_param(
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
$stmt->execute();

$reservation_id = $stmt->insert_id;

if ($reservation_id) {
    echo json_encode([
        "success"          => true,
        "reservation_id"   => $reservation_id,
        "reservation_code" => $reservation_code
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Booking failed"]);
}