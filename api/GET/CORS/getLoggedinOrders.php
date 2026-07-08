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

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(["orders" => []]);
    exit;
}

// Fetch menu images from DB instead of JSON file
$menuImages = [];
$menuStmt = $conn->prepare("SELECT id, image FROM menu_items WHERE tenant_id = ?");
$menuStmt->bind_param("i", $tenant_id);
$menuStmt->execute();
$menuResult = $menuStmt->get_result();
while ($m = $menuResult->fetch_assoc()) {
    $menuImages[$m['id']] = $m['image'];
}

$sql = "
SELECT 
    o.id AS order_id,
    o.plate_order_no,
    o.total_amount,
    o.order_status,
    o.created_at,
    i.menu_id,
    i.menu_name,
    i.quantity
FROM paid_orders o
LEFT JOIN paid_order_items i ON o.id = i.paid_order_id AND i.tenant_id = ?
WHERE o.user_id = ? AND o.tenant_id = ?
ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $tenant_id, $user_id, $tenant_id);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    $id = $row['order_id'];

    if (!isset($orders[$id])) {
        $orders[$id] = [
            "order_id"      => $id,
            "plate_order_no" => $row['plate_order_no'],
            "total_amount"  => $row['total_amount'],
            "order_status"  => $row['order_status'],
            "created_at"    => $row['created_at'],
            "items"         => []
        ];
    }

    if ($row['menu_id']) {
        $orders[$id]['items'][] = [
            "name"  => $row['menu_name'],
            "qty"   => $row['quantity'],
            "image" => $menuImages[$row['menu_id']] ?? null
        ];
    }
}

echo json_encode(["orders" => array_values($orders)]);