<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT tax, delivery_fee, service_fee FROM tax_settings WHERE tenant_id = ? LIMIT 1");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo json_encode(["tax" => 0, "delivery_fee" => 0, "service_fee" => 0]);
    exit;
}

echo json_encode($data);