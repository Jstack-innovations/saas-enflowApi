<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

require_once __DIR__ . "/../../SECURE/db.php";

/* ===== LOAD MENU JSON ===== */
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

/* ===== FETCH ORDERS + ITEMS (NO CLIENT DETAILS) ===== */
$sqlOrders = "
SELECT 
    o.id AS order_id,
    o.table_no,
    o.order_type,
    o.total_amount,
    o.created_at,
    o.status,
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
$res = $conn->query($sqlOrders);

$orders = [];
$totalPlaced = $totalServed = $totalDelivered = $totalPickup = $totalRevenue = 0;

while ($row = $res->fetch_assoc()) {
    $id = $row['order_id'];

    if (!isset($orders[$id])) {
        $orders[$id] = [
            "info" => [
                "order_id" => $row['order_id'],
                "table_no" => $row['table_no'],
                "order_type" => $row['order_type'],
                "total_amount" => $row['total_amount'],
                "created_at" => $row['created_at'],
                "status" => $row['status'],
                "plate_order_no" => $row['plate_order_no'],
                "order_status" => $row['order_status']
            ],
            "items" => []
        ];

        $totalPlaced++;
        if ($row['order_status'] === "Served") $totalServed++;
        if ($row['order_status'] === "Delivered") $totalDelivered++;
        if ($row['order_type'] === "pickup") $totalPickup++;
        $totalRevenue += floatval($row['total_amount']);
    }

    if ($row['menu_name']) {
        $orders[$id]['items'][] = [
            "name" => $row['menu_name'],
            "price" => $row['price'],
            "qty" => $row['quantity'],
            "image" => $menuImages[$row['menu_id']] ?? "images/default.jpg",
            "menu_id" => $row['menu_id'],
            "order_item_id" => $row['order_item_id']
        ];
    }
}

/* ===== DAILY REVENUE - LAST 7 DAYS ===== */
$dailyRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyRevenue[$date] = 0;
}

$sqlDaily = "
SELECT DATE(created_at) as order_date, SUM(total_amount) as total
FROM paid_orders
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY DATE(created_at)
";
$resDaily = $conn->query($sqlDaily);

while ($row = $resDaily->fetch_assoc()) {
    $dailyRevenue[$row['order_date']] = floatval($row['total']);
}

$dailyRevenueOutput = [];
foreach ($dailyRevenue as $date => $total) {
    $dayName = date('D', strtotime($date));
    $dailyRevenueOutput[] = ["day" => $dayName, "revenue" => $total];
}

/* ===== HOURLY REVENUE - TODAY ===== */
$hourlyRevenue = array_fill(0, 24, 0);

$sqlHourly = "
SELECT HOUR(created_at) as order_hour, SUM(total_amount) as total
FROM paid_orders
WHERE DATE(created_at) = CURDATE()
GROUP BY HOUR(created_at)
";
$resHourly = $conn->query($sqlHourly);

while ($row = $resHourly->fetch_assoc()) {
    $hour = intval($row['order_hour']);
    $hourlyRevenue[$hour] = floatval($row['total']);
}

$hourlyRevenueOutput = [];
foreach ($hourlyRevenue as $hour => $total) {
    $hourFormatted = date("gA", strtotime("$hour:00"));
    $hourlyRevenueOutput[] = ["hour" => $hourFormatted, "revenue" => $total];
}

/* ===== FINAL OUTPUT ===== */
echo json_encode([
    "orders" => $orders,
    "stats" => [
        "totalPlaced" => $totalPlaced,
        "totalServed" => $totalServed,
        "totalDelivered" => $totalDelivered,
        "totalPickup" => $totalPickup,
        "totalRevenue" => $totalRevenue
    ],
    "dailyRevenue" => $dailyRevenueOutput,
    "hourlyRevenue" => $hourlyRevenueOutput
]);
