<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["error" => "No data provided"]);
    exit;
}

if ($input['action'] === 'update') {

    $r = $input['reservation'];

    $stmt = $conn->prepare("
        UPDATE reservations
        SET name = ?, email = ?, phone = ?, booking_date = ?,
            transaction_id = ?, status = ?, reservation_code = ?
        WHERE id = ? AND tenant_id = ?
    ");
    $stmt->bind_param(
    "sssssissi",
    $r['name'],
    $r['email'],
    $r['phone'],
    $r['booking_date'],
    $r['transaction_id'],
    $r['status'],
    $r['reservation_code'],
    $r['id'],
    $tenant_id
);
    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}

if ($input['action'] === 'delete') {

    $id = $input['id'];

    $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $id, $tenant_id);
    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["error" => "Invalid action"]);