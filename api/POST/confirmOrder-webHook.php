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

$data           = json_decode(file_get_contents("php://input"), true);
$order_id       = $data['order_id'] ?? null;
$transaction_id = $data['transaction_id'] ?? '';

if (!$order_id || !$transaction_id) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$keyStmt = $conn->prepare("SELECT flutterwave_secret_key FROM tenants WHERE id = ?");
$keyStmt->bind_param("i", $tenant_id);
$keyStmt->execute();
$keyRow    = $keyStmt->get_result()->fetch_assoc();
$keyStmt->close();
$secretKey = $keyRow['flutterwave_secret_key'] ?? '';

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Secret key not found"]);
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
    echo json_encode(["status" => "error", "message" => "Payment gateway error"]);
    exit;
}

$result = json_decode($response, true);

if (
    !$result ||
    ($result['status'] ?? '') !== 'success' ||
    ($result['data']['status'] ?? '') !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

// Check if webhook already confirmed it
$chkStmt = $conn->prepare("SELECT id, status FROM paid_orders WHERE id = ? AND tenant_id = ?");
$chkStmt->bind_param("ii", $order_id, $tenant_id);
$chkStmt->execute();
$row = $chkStmt->get_result()->fetch_assoc();
$chkStmt->close();

if (!$row) {
    echo json_encode(["status" => "error", "message" => "Order not found"]);
    exit;
}

if ($row['status'] === 'paid') {
    echo json_encode(["status" => "success", "order_id" => $order_id]);
    exit;
}

// Payment verified but webhook hasn't fired yet
echo json_encode(["status" => "processing", "order_id" => $order_id]);