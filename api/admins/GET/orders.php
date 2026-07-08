<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

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
            "info"  => $row,
            "items" => []
        ];

        $totalPlaced++;
        if ($row['order_status'] == "Served")    $totalServed++;
        if ($row['order_status'] == "Delivered") $totalDelivered++;
        if ($row['order_type']   == "pickup")    $totalPickup++;
        $totalRevenue += floatval($row['total_amount']);
    }

    if ($row['menu_name']) {
        $orders[$id]['items'][] = [
            "name"          => $row['menu_name'],
            "price"         => $row['price'],
            "qty"           => $row['quantity'],
            "image"         => $menuImages[$row['menu_id']] ?? "images/default.jpg",
            "menu_id"       => $row['menu_id'],
            "paid_order_id" => $row['paid_order_id'],
            "order_item_id" => $row['order_item_id']
        ];
    }
}

echo json_encode([
    "orders" => $orders,
    "stats"  => [
        "totalPlaced"    => $totalPlaced,
        "totalServed"    => $totalServed,
        "totalDelivered" => $totalDelivered,
        "totalPickup"    => $totalPickup,
        "totalRevenue"   => $totalRevenue
    ]
]);