<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$session_code = $_GET['code'] ?? '';
if (!$session_code) {
    echo json_encode(["status" => "error", "message" => "Missing session code"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, name, phone, table_no, total_amount, status, created_at, plate_order_no
    FROM paid_orders
    WHERE session_code = ? AND tenant_id = ?
    LIMIT 1
");
$stmt->bind_param("si", $session_code, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Session not found"]);
    exit;
}

$order = $result->fetch_assoc();

if ($order['status'] === 'paid') {
    echo json_encode([
        "status" => "success",
        "order"  => $order,
        "items"  => []
    ]);
    exit;
}

if ($order['status'] !== 'open') {
    echo json_encode(["status" => "error", "message" => "Session closed"]);
    exit;
}

$order_id = $order['id'];

$itemStmt = $conn->prepare("
    SELECT menu_id, menu_name, price, quantity
    FROM paid_order_items
    WHERE paid_order_id = ? AND tenant_id = ?
");
$itemStmt->bind_param("ii", $order_id, $tenant_id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    "status" => "success",
    "order"  => $order,
    "items"  => $items
]);