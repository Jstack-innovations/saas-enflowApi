<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tenant");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT * FROM admins WHERE tenant_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$admins = $stmt->get_result();

$result = [];
while ($a = $admins->fetch_assoc()) {
    $result[] = $a;
}

echo json_encode(["admins" => $result]);