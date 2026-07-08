<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM paid_order_items WHERE paid_order_id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $id, $tenant_id);
$stmt->execute();

$stmt2 = $conn->prepare("DELETE FROM paid_orders WHERE id = ? AND tenant_id = ?");
$stmt2->bind_param("ii", $id, $tenant_id);
$stmt2->execute();

echo json_encode(["success" => true]);