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

$tenantStmt = $conn->prepare("SELECT notification_email, telegram_bot_token, telegram_chat_id FROM tenants WHERE id = ?");
$tenantStmt->bind_param("i", $tenant_id);
$tenantStmt->execute();
$tenantRow   = $tenantStmt->get_result()->fetch_assoc();
$notifyEmail = $tenantRow['notification_email'] ?? '';
$botToken    = $tenantRow['telegram_bot_token'] ?? '';
$chatId      = $tenantRow['telegram_chat_id'] ?? '';

$data = json_decode(file_get_contents("php://input"), true);

$name    = $data['name']     ?? '';
$phone   = $data['phone']    ?? '';
$tableNo = $data['table_no'] ?? '';
$cart    = $data['cart']     ?? [];
$amount  = $data['amount']   ?? 0;

if (!$name || !$phone || !$tableNo || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
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

$session_code = "TBL-" . $tableNo . "-" . strtoupper(substr(md5(uniqid()), 0, 5));
$plate_no     = "Artisan" . date("Ymd") . "GRILL" . str_pad(rand(0, 99), 2, "0", STR_PAD_LEFT);

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        INSERT INTO paid_orders
        (tenant_id, user_id, name, phone, table_no, order_type,
         total_amount, plate_order_no, status, session_code)
        VALUES (?, ?, ?, ?, ?, 'table', ?, ?, 'open', ?)
    ");
    $stmt->bind_param(
        "iisssdss",
        $tenant_id,
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

    $itemStmt = $conn->prepare("
        INSERT INTO paid_order_items
        (tenant_id, paid_order_id, menu_id, menu_name, price, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $updateStock = $conn->prepare("
        UPDATE menu_stock 
        SET stock = stock - ?, 
            available = CASE 
                WHEN stock - ? <= 0 THEN 0 
                ELSE 1 
            END
        WHERE menu_id = ? AND stock >= ? AND tenant_id = ?
    ");

    foreach ($cart as $item) {

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

        $itemStmt->bind_param(
            "iiisdi",
            $tenant_id,
            $order_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity']
        );
        $itemStmt->execute();
    }

    $bookStmt = $conn->prepare("
        INSERT INTO booked_tables (tenant_id, table_id, booked)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE booked = 1
    ");
    $bookStmt->bind_param("ii", $tenant_id, $tableNo);
    $bookStmt->execute();

    $conn->commit();

    require_once __DIR__ . '/../SECURE/resendMail.php';

    $itemsHtml = "";
    $itemsText = "";
    foreach ($cart as $item) {
        $itemsHtml .= "<p><b>{$item['name']}</b> | Qty: {$item['quantity']} | Price: {$item['price']}</p>";
        $itemsText .= "• {$item['name']} x{$item['quantity']} — ₦{$item['price']}\n";
    }

    sendEmail(
        $notifyEmail,
        "🔥 New Table Order - Table $tableNo",
        "
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
        "
    );

    $message = "🔥 *New Table Order Created!*\n\n🔑 *Session:* {$session_code}\n🧾 *Order ID:* #{$order_id}\n👤 *Name:* {$name}\n📞 *Phone:* {$phone}\n🪑 *Table:* {$tableNo}\n\n🍽️ *Items:*\n{$itemsText}\n💰 *Total:* ₦{$amount}";

    $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["chat_id" => $chatId, "text" => $message, "parse_mode" => "Markdown"]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

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