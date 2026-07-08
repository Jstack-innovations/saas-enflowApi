<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

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

$tenant_id = getTenantId($conn);

$tenantStmt = $conn->prepare("SELECT notification_email, telegram_bot_token, telegram_chat_id, flutterwave_secret_key FROM tenants WHERE id = ?");
$tenantStmt->bind_param("i", $tenant_id);
$tenantStmt->execute();
$tenantRow   = $tenantStmt->get_result()->fetch_assoc();
$notifyEmail = $tenantRow['notification_email'] ?? '';
$botToken    = $tenantRow['telegram_bot_token'] ?? '';
$chatId      = $tenantRow['telegram_chat_id'] ?? '';
$secretKey   = $tenantRow['flutterwave_secret_key'] ?? '';

$data = json_decode(file_get_contents("php://input"), true);

$session_code = $data['session_code']   ?? '';
$ref          = $data['transaction_id'] ?? '';

if (!$session_code || !$ref) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Secret key not found"]);
    exit;
}

// Production: SSL verification enabled
// $curl = curl_init();
// curl_setopt_array($curl, [
//     CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$ref/verify",
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
    CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$ref/verify",
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

if (curl_errno($curl)) {
    echo json_encode(["status" => "error", "message" => "Payment gateway error"]);
    exit;
}
curl_close($curl);

$result = json_decode($response, true);

if (!$result || $result['status'] !== 'success' || $result['data']['status'] !== 'successful') {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, total_amount, status, payment_ref
    FROM paid_orders
    WHERE session_code = ? AND tenant_id = ?
    LIMIT 1
");
$stmt->bind_param("si", $session_code, $tenant_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(["status" => "error", "message" => "Session not found"]);
    exit;
}

if ($order['status'] === 'paid') {
    echo json_encode(["status" => "error", "message" => "Already paid"]);
    exit;
}

if (($result['data']['tx_ref'] ?? '') !== $session_code) {
    echo json_encode(["status" => "error", "message" => "Invalid reference"]);
    exit;
}

$flutter_amount = (float)$result['data']['amount'];
$db_amount      = (float)$order['total_amount'];

if (abs($flutter_amount - $db_amount) > 0.01) {
    echo json_encode(["status" => "error", "message" => "Amount mismatch"]);
    exit;
}

$conn->begin_transaction();

try {

    $updateStmt = $conn->prepare("
        UPDATE paid_orders
        SET status = 'paid', payment_ref = ?
        WHERE session_code = ? AND status = 'open' AND tenant_id = ?
    ");
    $updateStmt->bind_param("ssi", $ref, $session_code, $tenant_id);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("Session not found or already closed");
    }

    $fetchStmt = $conn->prepare("
        SELECT id FROM paid_orders WHERE session_code = ? AND tenant_id = ? LIMIT 1
    ");
    $fetchStmt->bind_param("si", $session_code, $tenant_id);
    $fetchStmt->execute();
    $order_id = $fetchStmt->get_result()->fetch_assoc()['id'];

    $conn->commit();

    $message = "✅ *Session Closed — Payment Confirmed!*\n\n🧾 *Order ID:* #{$order_id}\n💳 *Transaction ID:* {$ref}\n💰 *Amount:* ₦" . number_format($flutter_amount, 2) . "\n🔑 *Session:* {$session_code}";

    $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["chat_id" => $chatId, "text" => $message, "parse_mode" => "Markdown"]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

    require_once __DIR__ . '/../SECURE/resendMail.php';
    sendEmail(
        $notifyEmail,
        "Session Closed — Order #{$order_id}",
        "<h2>✅ Session Closed</h2>
        <p><b>Order ID:</b> #{$order_id}</p>
        <p><b>Transaction ID:</b> {$ref}</p>
        <p><b>Amount:</b> ₦" . number_format($flutter_amount, 2) . "</p>
        <p><b>Session Code:</b> {$session_code}</p>"
    );

    echo json_encode([
        "status"   => "success",
        "order_id" => $order_id
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}