<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

/* ===== FETCH MENU IMAGES FROM DB ===== */
$menuImages = [];
$menuStmt = $conn->prepare("SELECT id, image FROM menu_items WHERE tenant_id = ?");
$menuStmt->bind_param("i", $tenant_id);
$menuStmt->execute();
$menuResult = $menuStmt->get_result();
while ($m = $menuResult->fetch_assoc()) {
    $menuImages[$m['id']] = $m['image'];
}

/* ===== FETCH ORDERS + ITEMS ===== */
$stmt = $conn->prepare("
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
    LEFT JOIN paid_order_items i ON o.id = i.paid_order_id AND i.tenant_id = ?
    WHERE o.tenant_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("ii", $tenant_id, $tenant_id);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
$totalPlaced = $totalServed = $totalDelivered = $totalPickup = $totalRevenue = 0;

while ($row = $res->fetch_assoc()) {
    $id = $row['order_id'];

    if (!isset($orders[$id])) {
        $orders[$id] = [
            "info" => [
                "order_id"       => $row['order_id'],
                "table_no"       => $row['table_no'],
                "order_type"     => $row['order_type'],
                "total_amount"   => $row['total_amount'],
                "created_at"     => $row['created_at'],
                "status"         => $row['status'],
                "plate_order_no" => $row['plate_order_no'],
                "order_status"   => $row['order_status']
            ],
            "items" => []
        ];

        $totalPlaced++;
        if ($row['order_status'] === "Served")    $totalServed++;
        if ($row['order_status'] === "Delivered") $totalDelivered++;
        if ($row['order_type']   === "pickup")    $totalPickup++;
        $totalRevenue += floatval($row['total_amount']);
    }

    if ($row['menu_name']) {
        $orders[$id]['items'][] = [
            "name"          => $row['menu_name'],
            "price"         => $row['price'],
            "qty"           => $row['quantity'],
            "image"         => $menuImages[$row['menu_id']] ?? "images/default.jpg",
            "menu_id"       => $row['menu_id'],
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

$stmtDaily = $conn->prepare("
    SELECT DATE(created_at) as order_date, SUM(total_amount) as total
    FROM paid_orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    AND tenant_id = ?
    GROUP BY DATE(created_at)
");
$stmtDaily->bind_param("i", $tenant_id);
$stmtDaily->execute();
$resDaily = $stmtDaily->get_result();
while ($row = $resDaily->fetch_assoc()) {
    $dailyRevenue[$row['order_date']] = floatval($row['total']);
}

$dailyRevenueOutput = [];
foreach ($dailyRevenue as $date => $total) {
    $dailyRevenueOutput[] = ["day" => date('M j', strtotime($date)), "revenue" => $total];
}

/* ===== HOURLY REVENUE - TODAY ===== */
$hourlyRevenue = array_fill(0, 24, 0);

$stmtHourly = $conn->prepare("
    SELECT HOUR(created_at) as order_hour, SUM(total_amount) as total
    FROM paid_orders
    WHERE DATE(created_at) = CURDATE() AND tenant_id = ?
    GROUP BY HOUR(created_at)
");
$stmtHourly->bind_param("i", $tenant_id);
$stmtHourly->execute();
$resHourly = $stmtHourly->get_result();
while ($row = $resHourly->fetch_assoc()) {
    $hourlyRevenue[intval($row['order_hour'])] = floatval($row['total']);
}

$hourlyRevenueOutput = [];
foreach ($hourlyRevenue as $hour => $total) {
    $hourlyRevenueOutput[] = ["hour" => date("gA", strtotime("$hour:00")), "revenue" => $total];
}

/* ===== TODAY ORDERS COUNT ===== */
$stmtToday = $conn->prepare("SELECT COUNT(*) as cnt FROM paid_orders WHERE DATE(created_at) = CURDATE() AND tenant_id = ?");
$stmtToday->bind_param("i", $tenant_id);
$stmtToday->execute();
$todayOrdersCount = intval($stmtToday->get_result()->fetch_assoc()['cnt'] ?? 0);

/* ===== LAST WEEK LUNCH REVENUE ===== */
$stmtLunch = $conn->prepare("
    SELECT SUM(total_amount) as total
    FROM paid_orders
    WHERE HOUR(created_at) BETWEEN 12 AND 14
    AND created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND tenant_id = ?
");
$stmtLunch->bind_param("i", $tenant_id);
$stmtLunch->execute();
$lastWeekLunchRevenue = floatval($stmtLunch->get_result()->fetch_assoc()['total'] ?? 0);

/* ===== TOP ITEM THIS WEEK ===== */
$stmtTopItem = $conn->prepare("
    SELECT i.menu_name, SUM(i.quantity) as total_qty
    FROM paid_order_items i
    JOIN paid_orders o ON i.paid_order_id = o.id
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND o.tenant_id = ?
    GROUP BY i.menu_name
    ORDER BY total_qty DESC
    LIMIT 1
");
$stmtTopItem->bind_param("i", $tenant_id);
$stmtTopItem->execute();
$topItemRow  = $stmtTopItem->get_result()->fetch_assoc();
$topItemName = $topItemRow['menu_name'] ?? 'N/A';
$topItemQty  = intval($topItemRow['total_qty'] ?? 0);

/* ===== TOTAL ITEMS QTY THIS WEEK ===== */
$stmtTotalQty = $conn->prepare("
    SELECT SUM(i.quantity) as total
    FROM paid_order_items i
    JOIN paid_orders o ON i.paid_order_id = o.id
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND o.tenant_id = ?
");
$stmtTotalQty->bind_param("i", $tenant_id);
$stmtTotalQty->execute();
$totalQtyWeek   = intval($stmtTotalQty->get_result()->fetch_assoc()['total'] ?? 0);
$topItemPercent = $totalQtyWeek ? round(($topItemQty / $totalQtyWeek) * 100, 1) : 0;

/* ===== FINAL OUTPUT ===== */
echo json_encode([
    "orders" => $orders,
    "stats"  => [
        "totalPlaced"    => $totalPlaced,
        "totalServed"    => $totalServed,
        "totalDelivered" => $totalDelivered,
        "totalPickup"    => $totalPickup,
        "totalRevenue"   => $totalRevenue
    ],
    "dailyRevenue"         => $dailyRevenueOutput,
    "hourlyRevenue"        => $hourlyRevenueOutput,
    "todayOrdersCount"     => $todayOrdersCount,
    "lastWeekLunchRevenue" => $lastWeekLunchRevenue,
    "topItemName"          => $topItemName,
    "topItemPercent"       => $topItemPercent,
]);