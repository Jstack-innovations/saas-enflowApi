<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("DELETE FROM admin_sessions WHERE admin_id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $GLOBALS['admin_id'], $tenant_id);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Logged out"]);