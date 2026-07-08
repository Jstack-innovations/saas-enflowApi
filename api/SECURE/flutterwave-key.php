<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/tenant.php';

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT flutterwave_public_key, flutterwave_secret_key FROM tenants WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !$row['flutterwave_public_key']) {
    http_response_code(404);
    echo json_encode(["error" => "Payment keys not configured for this tenant"]);
    exit;
}

echo json_encode([
    'publicKey' => $row['flutterwave_public_key'],
    'secretKey' => $row['flutterwave_secret_key']
]);