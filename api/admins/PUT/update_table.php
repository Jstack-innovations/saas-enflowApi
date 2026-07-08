<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["success" => false, "error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$data      = json_decode(file_get_contents("php://input"), true);
$id        = $data['id']        ?? null;
$action    = $data['action']    ?? null;
$booked    = $data['booked']    ?? 0;
$booked_id = $data['booked_id'] ?? null;

if (!$id || !$action) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

// ✅ 1. EDIT → restaurant_tables DB
if ($action === "edit") {

    $stmt = $conn->prepare("
        UPDATE restaurant_tables
        SET number = ?, seats = ?, description = ?, image = ?, amount = ?
        WHERE id = ? AND tenant_id = ?
    ");

    $number      = $data['number']      ?? null;
    $seats       = $data['seats']       ?? null;
    $description = $data['description'] ?? null;
    $image       = $data['image']       ?? null;
    $amount      = $data['amount']      ?? null;

    $stmt->bind_param("iissdii", $number, $seats, $description, $image, $amount, $id, $tenant_id);
    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}

// ✅ 2. BOOKING UPDATE → booked_tables DB
if ($action === "update") {

    if ($booked_id) {
        $stmt = $conn->prepare("UPDATE booked_tables SET booked = ? WHERE id = ? AND tenant_id = ?");
        $stmt->bind_param("iii", $booked, $booked_id, $tenant_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO booked_tables (tenant_id, table_id, booked) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $tenant_id, $id, $booked);
        $stmt->execute();
    }

    echo json_encode(["success" => true]);
    exit;
}

// ✅ 3. DELETE → booked_tables DB
if ($action === "delete") {

    if ($booked_id) {
        $stmt = $conn->prepare("DELETE FROM booked_tables WHERE id = ? AND tenant_id = ?");
        $stmt->bind_param("ii", $booked_id, $tenant_id);
        $stmt->execute();
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Nothing to delete"]);
    }

    exit;
}

echo json_encode(["success" => false, "error" => "Invalid action"]);