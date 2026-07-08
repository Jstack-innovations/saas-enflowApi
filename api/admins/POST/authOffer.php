<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT * FROM offers WHERE tenant_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

$offers = [];
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

echo json_encode($offers);