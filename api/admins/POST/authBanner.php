<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT address, discount_title, discount_subtitle FROM banners WHERE tenant_id = ? LIMIT 1");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode($row ?: (object)[]);