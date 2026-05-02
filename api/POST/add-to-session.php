<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../SECURE/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$session_code = $data['session_code'] ?? '';
$cart         = $data['cart'] ?? [];

if (!$session_code || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$conn->begin_transaction();

try {

    /* ===== FIND OPEN SESSION ===== */

    $stmt = $conn->prepare("
        SELECT id, total_amount 
        FROM paid_orders 
        WHERE session_code=? 
        AND status='open'
        LIMIT 1
    ");

    $stmt->bind_param("s", $session_code);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception("Session not found or closed");
    }

    $order = $result->fetch_assoc();
    $order_id = $order['id'];
    $current_total = $order['total_amount'];

    $itemStmt = $conn->prepare("
        SELECT id, quantity 
        FROM paid_order_items 
        WHERE paid_order_id=? 
        AND menu_id=?
        LIMIT 1
    ");

    $insertStmt = $conn->prepare("
        INSERT INTO paid_order_items
        (paid_order_id, menu_id, menu_name, price, quantity)
        VALUES (?, ?, ?, ?, ?)
    ");

    $updateQtyStmt = $conn->prepare("
        UPDATE paid_order_items 
        SET quantity = quantity + ?
        WHERE id=?
    ");

    $new_total = $current_total;

    $emailsToSend = [];

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

        /* ===== CHECK IF ITEM EXISTS ===== */
        $itemStmt->bind_param("ii", $order_id, $item['id']);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();

        if ($itemResult->num_rows === 1) {

            $existing = $itemResult->fetch_assoc();

            $updateQtyStmt->bind_param(
                "ii",
                $item['quantity'],
                $existing['id']
            );

            $updateQtyStmt->execute();

        } else {

    $insertStmt->bind_param(
        "iisdi",
        $order_id,
        $item['id'],
        $item['name'],
        $item['price'],
        $item['quantity']
    );

    $insertStmt->execute();

    /* ===== SEND EMAIL FOR NEW ITEM ===== */

    $emailsToSend[] = [
    "name" => $item['name'],
    "qty" => $item['quantity'],
    "price" => $item['price']
];
        }

        $new_total += ($item['price'] * $item['quantity']);
    }

    /* ===== UPDATE TOTAL ===== */
    $updateTotal = $conn->prepare("
        UPDATE paid_orders 
        SET total_amount=? 
        WHERE id=?
    ");

    $updateTotal->bind_param("di", $new_total, $order_id);
    $updateTotal->execute();

    $conn->commit();


    require_once __DIR__ . '/../SECURE/gmailApi/resend_mailer.php';

foreach ($emailsToSend as $item) {

    $body = "
        <h2>🔥 New Item Ordered</h2>
        <p><b>Session:</b> $session_code</p>
        <p><b>Order ID:</b> $order_id</p>
        <p><b>Item:</b> {$item['name']}</p>
        <p><b>Qty:</b> {$item['qty']}</p>
        <p><b>Price:</b> {$item['price']}</p>
    ";

    sendEmail(
        "wsamson630@gmail.com",
        "New Order Item - {$item['name']}",
        $body
    );
}
    

    echo json_encode([
        "status" => "success",
        "new_total" => $new_total
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
