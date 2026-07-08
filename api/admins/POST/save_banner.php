<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$data = json_decode(file_get_contents("php://input"), true);

$address          = $data['address']          ?? '';
$discount_title   = $data['discount_title']   ?? '';
$discount_subtitle = $data['discount_subtitle'] ?? '';

$stmt = $conn->prepare("
    UPDATE banners 
    SET address = ?, discount_title = ?, discount_subtitle = ?
    WHERE tenant_id = ?
");
$stmt->bind_param("sssi", $address, $discount_title, $discount_subtitle, $tenant_id);
$stmt->execute();

echo json_encode(["status" => "success"]);