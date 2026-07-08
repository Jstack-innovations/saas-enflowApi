<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Email, X-Tenant");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }

require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

// --- Verify email against admins table ---
$email = $_SERVER["HTTP_X_USER_EMAIL"] ?? "";
if (empty($email)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$check = $conn->prepare("SELECT id FROM admins WHERE LOWER(email) = LOWER(?) AND tenant_id = ?");
$check->bind_param("si", $email, $tenant_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}
$check->close();

// --- Revenue today ---
$stmtRev = $conn->prepare("SELECT SUM(total_amount) as total FROM paid_orders WHERE DATE(created_at) = CURDATE() AND tenant_id = ?");
$stmtRev->bind_param("i", $tenant_id);
$stmtRev->execute();
$revenueToday = floatval($stmtRev->get_result()->fetch_assoc()['total'] ?? 0);

// --- Orders today ---
$stmtOrd = $conn->prepare("SELECT COUNT(*) as cnt FROM paid_orders WHERE DATE(created_at) = CURDATE() AND tenant_id = ?");
$stmtOrd->bind_param("i", $tenant_id);
$stmtOrd->execute();
$ordersToday = intval($stmtOrd->get_result()->fetch_assoc()['cnt'] ?? 0);

// --- Tables seated + total from DB ---
$stmtTotal = $conn->prepare("SELECT COUNT(*) as cnt FROM restaurant_tables WHERE tenant_id = ?");
$stmtTotal->bind_param("i", $tenant_id);
$stmtTotal->execute();
$tablesTotal = intval($stmtTotal->get_result()->fetch_assoc()['cnt'] ?? 0);

$stmtBooked = $conn->prepare("SELECT COUNT(*) as cnt FROM booked_tables WHERE booked = 1 AND tenant_id = ?");
$stmtBooked->bind_param("i", $tenant_id);
$stmtBooked->execute();
$tablesSeated = intval($stmtBooked->get_result()->fetch_assoc()['cnt'] ?? 0);

// --- Daily revenue last 7 days ---
$dailyRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyRevenue[$date] = 0;
}

$stmtDaily = $conn->prepare("
    SELECT DATE(created_at) as order_date, SUM(total_amount) as total
    FROM paid_orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND tenant_id = ?
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

// --- Recent orders (last 6) ---
$stmtRecent = $conn->prepare("
    SELECT id, plate_order_no, table_no, order_type, total_amount, created_at, order_status, name, phone
    FROM paid_orders
    WHERE tenant_id = ?
    ORDER BY created_at DESC
    LIMIT 6
");
$stmtRecent->bind_param("i", $tenant_id);
$stmtRecent->execute();
$resRecent = $stmtRecent->get_result();

$recentOrders = [];
while ($row = $resRecent->fetch_assoc()) {
    $recentOrders[] = [
        "id"           => $row['id'],
        "plate_no"     => $row['plate_order_no'],
        "table_no"     => $row['table_no'],
        "order_type"   => $row['order_type'],
        "total_amount" => floatval($row['total_amount']),
        "created_at"   => $row['created_at'],
        "order_status" => $row['order_status'],
        "name"         => $row['name'],
        "phone"        => $row['phone'],
    ];
}

// --- Top items today ---
$stmtTop = $conn->prepare("
    SELECT i.menu_name, SUM(i.quantity) as total_qty
    FROM paid_order_items i
    JOIN paid_orders o ON i.paid_order_id = o.id
    WHERE DATE(o.created_at) = CURDATE() AND o.tenant_id = ?
    GROUP BY i.menu_name
    ORDER BY total_qty DESC
    LIMIT 4
");
$stmtTop->bind_param("i", $tenant_id);
$stmtTop->execute();
$resTop = $stmtTop->get_result();

$topItemsTotal = 0;
$topItemsRaw   = [];
while ($row = $resTop->fetch_assoc()) {
    $topItemsRaw[] = $row;
    $topItemsTotal += intval($row['total_qty']);
}

$topItems = [];
foreach ($topItemsRaw as $row) {
    $topItems[] = [
        "name" => $row['menu_name'],
        "qty"  => intval($row['total_qty']),
        "pct"  => $topItemsTotal ? round((intval($row['total_qty']) / $topItemsTotal) * 100) : 0,
    ];
}

echo json_encode([
    "stats" => [
        "revenue_today"    => $revenueToday,
        "orders_today"     => $ordersToday,
        "tables_seated"    => $tablesSeated,
        "tables_total"     => $tablesTotal,
        "zara_chats_today" => 0,
        "daily_revenue"    => $dailyRevenueOutput,
        "recent_orders"    => $recentOrders,
        "top_items"        => $topItems
    ]
]);