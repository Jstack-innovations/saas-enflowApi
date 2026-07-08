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

$id        = $input['id'];
$full_name = $input['full_name'];
$email     = $input['email'];
$phone     = $input['phone'];
$status    = $input['status'];

$stmt = $conn->prepare("
    UPDATE users
    SET full_name = ?, email = ?, phone = ?, status = ?
    WHERE id = ? AND tenant_id = ?
");
$stmt->bind_param("ssssii", $full_name, $email, $phone, $status, $id, $tenant_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}