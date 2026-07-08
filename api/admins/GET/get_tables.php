<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

/* ===== FETCH TABLES FROM DB ===== */
$stmtTables = $conn->prepare("SELECT * FROM restaurant_tables WHERE tenant_id = ? ORDER BY id ASC");
$stmtTables->bind_param("i", $tenant_id);
$stmtTables->execute();
$tablesResult = $stmtTables->get_result();

$floors = [];
while ($row = $tablesResult->fetch_assoc()) {
    $floors[$row['floor']][] = $row;
}

/* ===== FETCH BOOKED TABLES ===== */
$stmtBooked = $conn->prepare("SELECT * FROM booked_tables WHERE tenant_id = ?");
$stmtBooked->bind_param("i", $tenant_id);
$stmtBooked->execute();
$bookedResult = $stmtBooked->get_result();

$bookedRows = [];
while ($row = $bookedResult->fetch_assoc()) {
    $bookedRows[$row['table_id']] = $row;
}

/* ===== BUILD RESPONSE ===== */
$final = ["floors" => []];

foreach ($floors as $floorName => $tables) {
    $final["floors"][$floorName] = [];

    foreach ($tables as $table) {
        $id      = $table['id'];
        $booking = $bookedRows[$id] ?? null;

        $final["floors"][$floorName][] = [
            "id"          => $table['id'],
            "number"      => $table['number'],
            "seats"       => $table['seats'],
            "amount"      => $table['amount'],
            "image"       => $table['image'],
            "description" => $table['description'],
            "booked"      => isset($booking['booked']) ? (int)$booking['booked'] : 0,
            "booked_id"   => $booking['id'] ?? null
        ];
    }
}

echo json_encode($final);