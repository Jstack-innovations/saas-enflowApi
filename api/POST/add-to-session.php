<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../SECURE/db.php';
require_once __DIR__ . '/../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

// FIX 1: Fetch telegram + email credentials from tenants table (not getenv)
$tenantStmt = $conn->prepare("SELECT notification_email, telegram_bot_token, telegram_chat_id FROM tenants WHERE id = ?");
$tenantStmt->bind_param("i", $tenant_id);
$tenantStmt->execute();
$tenantRow   = $tenantStmt->get_result()->fetch_assoc();
$notifyEmail = $tenantRow['notification_email'] ?? '';
$botToken    = $tenantRow['telegram_bot_token'] ?? '';
$chatId      = $tenantRow['telegram_chat_id'] ?? '';

$data = json_decode(file_get_contents("php://input"), true);

$session_code = $data['session_code'] ?? '';
$cart         = $data['cart'] ?? [];

if (!$session_code || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        SELECT id, total_amount, table_no
        FROM paid_orders 
        WHERE session_code = ? AND status = 'open' AND tenant_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("si", $session_code, $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception("Session not found or closed");
    }

    $order         = $result->fetch_assoc();
    $order_id      = $order['id'];
    $current_total = $order['total_amount'];
    $table_no      = $order['table_no'];

    $itemStmt = $conn->prepare("
        SELECT id, quantity 
        FROM paid_order_items 
        WHERE paid_order_id = ? AND menu_id = ? AND tenant_id = ?
        LIMIT 1
    ");

    $insertStmt = $conn->prepare("
        INSERT INTO paid_order_items
        (tenant_id, paid_order_id, menu_id, menu_name, price, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $updateQtyStmt = $conn->prepare("
        UPDATE paid_order_items 
        SET quantity = quantity + ?
        WHERE id = ? AND tenant_id = ?
    ");

    $new_total    = $current_total;
    $emailsToSend = [];

    foreach ($cart as $item) {

        $updateStock = $conn->prepare("
            UPDATE menu_stock 
            SET stock = stock - ?, 
                available = CASE 
                    WHEN stock - ? <= 0 THEN 0 
                    ELSE 1 
                END
            WHERE menu_id = ? AND stock >= ? AND tenant_id = ?
        ");
        $updateStock->bind_param(
            "iiiii",
            $item['quantity'],
            $item['quantity'],
            $item['id'],
            $item['quantity'],
            $tenant_id
        );
        $updateStock->execute();

        if ($updateStock->affected_rows === 0) {
            throw new Exception("Not enough stock for " . $item['name']);
        }

        $itemStmt->bind_param("iii", $order_id, $item['id'], $tenant_id);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();

        if ($itemResult->num_rows === 1) {

            $existing = $itemResult->fetch_assoc();
            $updateQtyStmt->bind_param("iii", $item['quantity'], $existing['id'], $tenant_id);
            $updateQtyStmt->execute();

        } else {

            $insertStmt->bind_param(
                "iiisdi",
                $tenant_id,
                $order_id,
                $item['id'],
                $item['name'],
                $item['price'],
                $item['quantity']
            );
            $insertStmt->execute();

            $emailsToSend[] = [
                "name"  => $item['name'],
                "qty"   => $item['quantity'],
                "price" => $item['price']
            ];
        }

        $new_total += ($item['price'] * $item['quantity']);
    }

    $updateTotal = $conn->prepare("
        UPDATE paid_orders 
        SET total_amount = ? 
        WHERE id = ? AND tenant_id = ?
    ");
    $updateTotal->bind_param("dii", $new_total, $order_id, $tenant_id);
    $updateTotal->execute();

    $conn->commit();

    // FIX 2: Correct resendMail path (matches createSession and closeSession)
    require_once __DIR__ . '/../SECURE/resendMail.php';

    foreach ($emailsToSend as $item) {

        $message = "🛒 *New Item Added to Session!*\n\n🔑 *Session:* {$session_code}\n🧾 *Order ID:* #{$order_id}\n🪑 *Table:* {$table_no}\n🍽️ *Item:* {$item['name']}\n📦 *Qty:* {$item['qty']}\n💰 *Price:* ₦{$item['price']}";

        $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["chat_id" => $chatId, "text" => $message, "parse_mode" => "Markdown"]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);

        sendEmail(
            $notifyEmail,
            "New Order Item - {$item['name']}",
            "<h2>🔥 New Item Ordered</h2>
            <p><b>Session:</b> {$session_code}</p>
            <p><b>Order ID:</b> #{$order_id}</p>
            <p><b>Table No:</b> {$table_no}</p>
            <p><b>Item:</b> {$item['name']}</p>
            <p><b>Qty:</b> {$item['qty']}</p>
            <p><b>Price:</b> ₦{$item['price']}</p>"
        );
    }

    echo json_encode([
        "status"    => "success",
        "new_total" => $new_total
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
