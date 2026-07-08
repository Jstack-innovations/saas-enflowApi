<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tenant");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$data = json_decode(file_get_contents("php://input"), true);

$id       = intval($data['id']       ?? 0);
$quantity = intval($data['quantity'] ?? 0);
$menu_id  = intval($data['menu_id']  ?? 0);

$stmt = $conn->prepare("UPDATE kitchen_production SET status = 'done' WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $id, $tenant_id);
$stmt->execute();

$stmt2 = $conn->prepare("UPDATE menu_stock SET stock = stock + ? WHERE menu_id = ? AND tenant_id = ?");
$stmt2->bind_param("iii", $quantity, $menu_id, $tenant_id);
$stmt2->execute();

echo json_encode(["success" => true]);