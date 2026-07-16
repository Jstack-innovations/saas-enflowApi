<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . '/../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../SECURE/tenant.php';

$tenant_id = getTenantId($conn);
$tenantName = getTenantName($conn, $tenant_id);

$data = json_decode(file_get_contents("php://input"), true);

$name        = $data['name'] ?? '';
$phone       = $data['phone'] ?? '';
$tableNo     = $data['table_no'] ?? '';
$address     = $data['address'] ?? '';
$pickup_time = $data['pickup_time'] ?? '';
$orderType   = $data['order_type'] ?? 'table';
$total       = $data['amount'] ?? 0;
$cart        = $data['cart'] ?? [];

if (!$name || !$phone || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

if ($orderType === 'pickup') {
    $tableNo = '';
}

$sessionToken = $data['session_token'] ?? null;
$user_id = null;

if ($sessionToken) {
    $sessionStmt = $conn->prepare("
        SELECT user_id 
        FROM user_sessions 
        WHERE session_token = ? AND expires_at > NOW() AND tenant_id = ?
        LIMIT 1
    ");
    $sessionStmt->bind_param("si", $sessionToken, $tenant_id);
    $sessionStmt->execute();
    $sessionResult = $sessionStmt->get_result();

    if ($sessionResult->num_rows === 1) {
        $user_id = $sessionResult->fetch_assoc()['user_id'];
    }
}

if (!$user_id && $phone) {
    $userCheck = $conn->prepare("
        SELECT id 
        FROM users 
        WHERE phone = ? AND tenant_id = ?
        LIMIT 1
    ");
    $userCheck->bind_param("si", $phone, $tenant_id);
    $userCheck->execute();
    $userResult = $userCheck->get_result();

    if ($userResult->num_rows === 1) {
        $user_id = $userResult->fetch_assoc()['id'];
    }
}

$namePrefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $tenantName));
$namePrefix = substr($namePrefix, 0, 8);
if ($namePrefix === '') { $namePrefix = 'TENANT'; }
$plate_no = $namePrefix . date("Ymd") . str_pad(rand(0, 99), 2, "0", STR_PAD_LEFT);


$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        INSERT INTO paid_orders
        (tenant_id, user_id, name, phone, table_no, full_address, order_type,
         total_amount, plate_order_no, status, pickup_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $status = "payment_pending";

    $stmt->bind_param(
        "iisssssdsss",
        $tenant_id,
        $user_id,
        $name,
        $phone,
        $tableNo,
        $address,
        $orderType,
        $total,
        $plate_no,
        $status,
        $pickup_time
    );

    $stmt->execute();
    $paid_order_id = $stmt->insert_id;

    $itemStmt = $conn->prepare("
        INSERT INTO paid_order_items
        (tenant_id, paid_order_id, menu_id, menu_name, price, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {
        $itemStmt->bind_param(
            "iiisdi",
            $tenant_id,
            $paid_order_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity']
        );
        $itemStmt->execute();
    }

    $conn->commit();

echo json_encode([
    "status"   => "success",
    "order_id" => $paid_order_id,
    "tenant_id" => $tenant_id,
    "user_id"  => $user_id
]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}