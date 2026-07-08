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
$id    = $input['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $id, $tenant_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}