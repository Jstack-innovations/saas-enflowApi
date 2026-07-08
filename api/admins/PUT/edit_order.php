<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing order id"]);
    exit;
}

/* =========================
   GET SINGLE ORDER + ITEMS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $menuImages = [];
    $menuStmt = $conn->prepare("SELECT id, image FROM menu_items WHERE tenant_id = ?");
    $menuStmt->bind_param("i", $tenant_id);
    $menuStmt->execute();
    $menuResult = $menuStmt->get_result();
    while ($m = $menuResult->fetch_assoc()) {
        $menuImages[$m['id']] = $m['image'];
    }

    $stmt = $conn->prepare("SELECT * FROM paid_orders WHERE id = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $id, $tenant_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(["error" => "Order not found"]);
        exit;
    }

    $itemStmt = $conn->prepare("SELECT * FROM paid_order_items WHERE paid_order_id = ? AND tenant_id = ?");
    $itemStmt->bind_param("ii", $id, $tenant_id);
    $itemStmt->execute();
    $itemsRes = $itemStmt->get_result();

    $items = [];
    while ($row = $itemsRes->fetch_assoc()) {
        $items[] = [
            "name"          => $row['menu_name'],
            "price"         => $row['price'],
            "qty"           => $row['quantity'],
            "image"         => $menuImages[$row['menu_id']] ?? "images/default.jpg",
            "menu_id"       => $row['menu_id'],
            "order_item_id" => $row['id']
        ];
    }

    echo json_encode([
        "info"  => $order,
        "items" => $items
    ]);
    exit;
}

/* =========================
   UPDATE ORDER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("
        UPDATE paid_orders SET 
        name = ?, phone = ?, table_no = ?, order_type = ?, total_amount = ?, 
        payment_ref = ?, order_status = ?, full_address = ?, plate_order_no = ? 
        WHERE id = ? AND tenant_id = ?
    ");
    $stmt->bind_param(
        "sssssssssii",
        $data['name'],
        $data['phone'],
        $data['table_no'],
        $data['order_type'],
        $data['total_amount'],
        $data['payment_ref'],
        $data['order_status'],
        $data['full_address'],
        $data['plate_order_no'],
        $id,
        $tenant_id
    );
    $stmt->execute();

    echo json_encode(["success" => true]);
}