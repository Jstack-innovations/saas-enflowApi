<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tenant");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT address, discount_title, discount_subtitle FROM banners WHERE tenant_id = ? LIMIT 1");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "query failed"]);
    exit;
}

$row = $result->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(["error" => "banner not found"]);
    exit;
}

echo json_encode([
    "address" => $row["address"],
    "discount" => [
        "title" => $row["discount_title"],
        "subtitle" => $row["discount_subtitle"]
    ]
]);