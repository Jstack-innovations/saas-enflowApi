<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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



$session_code = $_GET['code'] ?? '';

if (!$session_code) {
    echo json_encode(["status" => "error", "message" => "Missing session code"]);
    exit;
}



/* ===== FETCH SESSION ORDER ===== */

$stmt = $conn->prepare("
    SELECT id, name, phone, table_no, total_amount, status, created_at, plate_order_no
    FROM paid_orders
    WHERE session_code = ?
    LIMIT 1
");

$stmt->bind_param("s", $session_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Session not found"]);
    exit;
}

$order = $result->fetch_assoc();
$order_id = $order['id'];



/* ===== FETCH ORDER ITEMS ===== */

$itemStmt = $conn->prepare("
    SELECT menu_id, menu_name, price, quantity
    FROM paid_order_items
    WHERE paid_order_id = ?
");

$itemStmt->bind_param("i", $order_id);
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

