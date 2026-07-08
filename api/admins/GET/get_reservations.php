<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

header('Content-Type: application/json');

$tenant_id = getTenantId($conn);

/* ===== FETCH RESERVATIONS ===== */
$stmt = $conn->prepare("SELECT * FROM reservations WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$res = $stmt->get_result();

$reservations = [];
while ($row = $res->fetch_assoc()) {
    $reservations[] = $row;
}

/* ===== FETCH TABLES FROM DB ===== */
$stmtTables = $conn->prepare("SELECT * FROM restaurant_tables WHERE tenant_id = ? ORDER BY id ASC");
$stmtTables->bind_param("i", $tenant_id);
$stmtTables->execute();
$tablesResult = $stmtTables->get_result();

$tables = [];
while ($row = $tablesResult->fetch_assoc()) {
    $tables[] = $row;
}

echo json_encode([
    "reservations" => $reservations,
    "tables"       => $tables
]);