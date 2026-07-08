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

$data     = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(["status" => "error", "message" => "Missing order_id"]);
    exit;
}

$stmt = $conn->prepare("SELECT status FROM paid_orders WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $order_id, $tenant_id);
$stmt->execute();
$stmt->bind_result($orderStatus);
$stmt->fetch();
$stmt->close();

if ($orderStatus === 'paid') {
    echo json_encode(["status" => "success", "order_id" => $order_id]);
} else {
    echo json_encode(["status" => "pending", "message" => "Payment not yet confirmed"]);
}
