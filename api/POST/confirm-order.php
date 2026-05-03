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

require_once __DIR__ . '/../SECURE/gmailApi/resend_mailer.php';



$data = json_decode(file_get_contents("php://input"), true);

$order_id    = $data['order_id'] ?? null;
$transaction_id = $data['transaction_id'] ?? '';
$tx_ref = $data['tx_ref'] ?? '';
$total       = $data['amount'] ?? 0;
$orderType   = $data['order_type'] ?? 'table';
$tableNo     = $data['table_no'] ?? '';
$cart        = $data['cart'] ?? [];



/* ===== VALIDATION ===== */

if (!$order_id || !$ref || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}




ob_start();
include __DIR__ . '/../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();
$keyData = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode([
        "status"  => "error",
        "message" => "Secret key not found"
    ]);
    exit;
}




$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer $secretKey",
    "Content-Type: application/json"
  ),
));

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (
    $result['status'] !== 'success' ||
    $result['data']['status'] !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

/* Optional: check amount matches */
if ((float)$result['data']['amount'] !== (float)$total) {
    echo json_encode(["status" => "error", "message" => "Amount mismatch"]);
    exit;
}




$conn->begin_transaction();

try {

    /* ===== UPDATE ORDER: payment_pending → paid ===== */
    /* This is when kitchen gets visibility of the order */

    $updateStmt = $conn->prepare("
        UPDATE paid_orders
        SET status = 'paid', payment_ref = ?
        WHERE id = ? AND status = 'payment_pending'
    ");

    $updateStmt->bind_param("si", $ref, $order_id);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("Order not found or already confirmed");
    }

    /* ===== TABLE BOOKING ===== */

    if ($orderType === 'table' && !empty($tableNo)) {

        $conn->query("
            INSERT INTO booked_tables (table_id, booked)
            VALUES ($tableNo, 1)
            ON DUPLICATE KEY UPDATE booked=1
        ");
    }

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

    foreach ($cart as $item) {

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
    }

    $conn->commit();


    /* ===== SEND EMAIL NOTIFICATION ===== */

$itemsText = "";

foreach ($cart as $item) {
    $itemsText .= "
        <li>{$item['name']} x {$item['quantity']}</li>
    ";
}

$emailBody = "
<h2>🔥 New Paid Order</h2>

<p><b>Order ID:</b> $order_id</p>
<p><b>Payment Ref:</b> $ref</p>
<p><b>Table:</b> $tableNo</p>
<p><b>Type:</b> $orderType</p>

<h3>Items:</h3>
<ul>
    $itemsText
</ul>

<p><b>Total:</b> $$total</p>
";

sendEmail(
    "wsamson630@gmail.com", // 👈 change to admin/kitchen email
    "New Paid Order #$order_id",
    $emailBody
);
    

    echo json_encode([
        "status"   => "success",
        "order_id" => $order_id
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}

