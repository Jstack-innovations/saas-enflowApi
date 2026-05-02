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

$name     = $data['name']     ?? '';
$phone    = $data['phone']    ?? '';
$tableNo  = $data['table_no'] ?? '';
$cart     = $data['cart']     ?? [];
$amount   = $data['amount']   ?? 0;



/* ===== VALIDATION ===== */

if (!$name || !$phone || !$tableNo || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
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



/* ===== GENERATE SESSION CODE ===== */

$session_code = "TBL-" . $tableNo . "-" . strtoupper(substr(md5(uniqid()), 0, 5));

$plate_no = "Artisan" . date("Ymd") . "GRILL" . str_pad(rand(0, 99), 2, "0", STR_PAD_LEFT);



$conn->begin_transaction();

try {

    /* ===== INSERT ORDER WITH STATUS = open ===== */
    /* Kitchen sees this immediately — customer is physically at the table */

    $stmt = $conn->prepare("
        INSERT INTO paid_orders
        (user_id, name, phone, table_no, order_type,
         total_amount, plate_order_no, status, session_code)
        VALUES (?, ?, ?, ?, 'table', ?, ?, 'open', ?)
    ");

    $stmt->bind_param(
        "isssdss",
        $user_id,
        $name,
        $phone,
        $tableNo,
        $amount,
        $plate_no,
        $session_code
    );

    $stmt->execute();
    $order_id = $stmt->insert_id;

    /* ===== INSERT ORDER ITEMS ===== */

    $itemStmt = $conn->prepare("
        INSERT INTO paid_order_items
        (paid_order_id, menu_id, menu_name, price, quantity)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {

        /* ===== REDUCE STOCK ===== */
        $updateStock = $conn->prepare("
            UPDATE menu_stock 
            SET stock = stock - ?, 
                available = CASE 
                    WHEN stock - ? <= 0 THEN 0 
                    ELSE 1 
                END
            WHERE menu_id = ? 
            AND stock >= ?
        ");

        $updateStock->bind_param(
            "iiii",
            $item['quantity'],
            $item['quantity'],
            $item['id'],
            $item['quantity']
        );

        $updateStock->execute();

        if ($updateStock->affected_rows === 0) {
            throw new Exception("Not enough stock for " . $item['name']);
        }

        /* ===== INSERT ITEM ===== */
        $itemStmt->bind_param(
            "iisdi",
            $order_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity']
        );

        $itemStmt->execute();
    }

    /* ===== BOOK THE TABLE ===== */

    $conn->query("
        INSERT INTO booked_tables (table_id, booked)
        VALUES ($tableNo, 1)
        ON DUPLICATE KEY UPDATE booked=1
    ");

    $conn->commit();

    /* ===== SEND EMAIL FOR NEW TABLE ORDER ===== */

require_once __DIR__ . '/../SECURE/gmailApi/resend_mailer.php';

$itemsHtml = "";
foreach ($cart as $item) {
    $itemsHtml .= "
        <p>
            <b>{$item['name']}</b> 
            | Qty: {$item['quantity']} 
            | Price: {$item['price']}
        </p>
    ";
}

$body = "
    <h2>🔥 New Table Order Created</h2>
    <p><b>Session Code:</b> $session_code</p>
    <p><b>Order ID:</b> $order_id</p>
    <p><b>Name:</b> $name</p>
    <p><b>Phone:</b> $phone</p>
    <p><b>Table No:</b> $tableNo</p>
    <hr>
    <h3>Items:</h3>
    $itemsHtml
    <hr>
    <p><b>Total Amount:</b> $amount</p>
";

sendEmail(
    "yourrestaurant@email.com",
    "🔥 New Table Order - Table $tableNo",
    $body
);

    echo json_encode([
        "status"       => "success",
        "order_id"     => $order_id,
        "session_code" => $session_code
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}


