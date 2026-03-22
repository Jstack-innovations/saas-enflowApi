<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

session_start();

/*if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}*/

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

/* Load menu JSON */
$menuFile = __DIR__ . "/../../GET/JSON/menu.json";

$menuJson = [];

if (file_exists($menuFile)) {
    $menuJson = json_decode(file_get_contents($menuFile), true) ?? [];
}

$menuImages = [];
foreach ($menuJson as $category) {
    foreach ($category as $m) {
        $menuImages[$m['id']] = $m['image'];
    }
}

/* Fetch orders + items */
$sql = "
SELECT 
    o.id AS order_id,
    o.user_id,
    o.name,
    o.phone,
    o.table_no,
    o.order_type,
    o.total_amount,
    o.payment_ref,
    o.created_at,
    o.status,
    o.full_address,
    o.pickup_time,
    o.plate_order_no,
    o.order_status,
    i.id AS order_item_id,
    i.paid_order_id,
    i.menu_id,
    i.menu_name,
    i.price,
    i.quantity
FROM paid_orders o
LEFT JOIN paid_order_items i ON o.id = i.paid_order_id
ORDER BY o.created_at DESC
";

$res = $conn->query($sql);

$orders = [];

$totalPlaced = 0;
$totalServed = 0;
$totalDelivered = 0;
$totalPickup = 0;
$totalRevenue = 0;

while ($row = $res->fetch_assoc()) {
    $id = $row['order_id'];

    if (!isset($orders[$id])) {
        $orders[$id] = [
            "info" => $row,
            "items" => []
        ];

        $totalPlaced++;
        if ($row['order_status'] == "Served") $totalServed++;
        if ($row['order_status'] == "Delivered") $totalDelivered++;
        if ($row['order_type'] == "pickup") $totalPickup++;

        $totalRevenue += floatval($row['total_amount']);
    }

    if ($row['menu_name']) {
        $orders[$id]['items'][] = [
            "name" => $row['menu_name'],
            "price" => $row['price'],
            "qty" => $row['quantity'],
            "image" => $menuImages[$row['menu_id']] ?? "images/default.jpg",
            "menu_id" => $row['menu_id'],
            "paid_order_id" => $row['paid_order_id'],
            "order_item_id" => $row['order_item_id']
        ];
    }
}

echo json_encode([
    "orders" => $orders,
    "stats" => [
        "totalPlaced" => $totalPlaced,
        "totalServed" => $totalServed,
        "totalDelivered" => $totalDelivered,
        "totalPickup" => $totalPickup,
        "totalRevenue" => $totalRevenue
    ]
]);
