<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["success" => false, "error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(["success" => false, "error" => "Invalid or missing JSON body"]);
    exit;
}

$action = $data['action'] ?? "update";

if ($action === "update") {

    $tax          = floatval($data['tax']          ?? 0);
    $delivery_fee = floatval($data['delivery_fee'] ?? 0);
    $service_fee  = floatval($data['service_fee']  ?? 0);

    $stmt = $conn->prepare("
        INSERT INTO tax_settings (tenant_id, tax, delivery_fee, service_fee)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE tax = ?, delivery_fee = ?, service_fee = ?
    ");
    $stmt->bind_param("idddddd", $tenant_id, $tax, $delivery_fee, $service_fee, $tax, $delivery_fee, $service_fee);
    $stmt->execute();
}

echo json_encode(["success" => true]);