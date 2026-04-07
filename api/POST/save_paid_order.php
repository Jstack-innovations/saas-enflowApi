<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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



$data = json_decode(file_get_contents("php://input"), true);

$name        = $data['name'] ?? '';
$phone       = $data['phone'] ?? '';
$tableNo     = $data['table_no'] ?? '';
$address     = $data['address'] ?? '';
$pickup_time = $data['pickup_time'] ?? '';
$orderType   = $data['order_type'] ?? 'table';
$total       = $data['amount'] ?? 0;
$ref         = $data['transaction_id'] ?? '';
$cart        = $data['cart'] ?? [];





ob_start();
include __DIR__ . '/../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();
$keyData = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Secret key not found"
    ]);
    exit;
}




/* ===== SESSION TOKEN (IF USER IS LOGGED IN) ===== */
$sessionToken = $data['session_token'] ?? null;
$user_id = null;

if ($sessionToken) {

    $sessionStmt = $conn->prepare("
        SELECT user_id 
        FROM user_sessions 
        WHERE session_token=? 
        AND expires_at > NOW()
        LIMIT 1
    ");

    $sessionStmt->bind_param("s", $sessionToken);
    $sessionStmt->execute();

    $sessionResult = $sessionStmt->get_result();

    if ($sessionResult->num_rows === 1) {
        $sessionRow = $sessionResult->fetch_assoc();
        $user_id = $sessionRow['user_id'];
    }
}

/* ===== IF NOT LOGGED IN → TRY MATCH PHONE ===== */
if (!$user_id && $phone) {

    $userCheck = $conn->prepare("
        SELECT id 
        FROM users 
        WHERE phone=?
        LIMIT 1
    ");

    $userCheck->bind_param("s", $phone);
    $userCheck->execute();

    $userResult = $userCheck->get_result();

    if ($userResult->num_rows === 1) {
        $userRow = $userResult->fetch_assoc();
        $user_id = $userRow['id'];
    }
}

/* ===== VALIDATION ===== */

if (!$name || !$phone || !$ref || empty($cart)) {
    echo json_encode(["status"=>"error","message"=>"Missing data"]);
    exit;
}

/* ===== PICKUP HANDLING ===== */

if ($orderType === 'pickup') {
    $tableNo = '';
}

$plate_no = "Artisan" . date("Ymd") . "GRILL" . str_pad(rand(0, 99), 2, "0", STR_PAD_LEFT);

$conn->begin_transaction();

try {

    /* ===== INSERT ORDER (WITH USER ID COLUMN) ===== */

    $stmt = $conn->prepare("
        INSERT INTO paid_orders
        (user_id, name, phone, table_no, full_address, order_type,
         total_amount, payment_ref, plate_order_no, status, pickup_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $status = "paid";

    $stmt->bind_param(
        "isssssdssss",
        $user_id,
        $name,
        $phone,
        $tableNo,
        $address,
        $orderType,
        $total,
        $ref,
        $plate_no,
        $status,
        $pickup_time
    );

    $stmt->execute();
    $paid_order_id = $stmt->insert_id;

    /* ===== TABLE BOOKING ===== */

    if ($orderType === 'table' && !empty($tableNo)) {

        $conn->query("
            INSERT INTO booked_tables (table_id, booked)
            VALUES ($tableNo,1)
            ON DUPLICATE KEY UPDATE booked=1
        ");
    }

    /* ===== INSERT ORDER ITEMS ===== */

    $itemStmt = $conn->prepare("
        INSERT INTO paid_order_items
        (paid_order_id, menu_id, menu_name, price, quantity)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {

        $itemStmt->bind_param(
            "iisdi",
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
        "status" => "success",
        "order_id" => $paid_order_id,
        "user_id" => $user_id
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);
}
