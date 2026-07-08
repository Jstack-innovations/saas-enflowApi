<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../SECURE/db.php';
require_once __DIR__ . '/../SECURE/tenant.php';
require_once __DIR__ . '/../SECURE/resendMail.php';

$tenant_id = getTenantId($conn);

$data = json_decode(file_get_contents("php://input"), true);

$order_id       = $data['order_id'] ?? null;
$transaction_id = $data['transaction_id'] ?? '';
$orderType      = $data['order_type'] ?? 'table';
$tableNo        = $data['table_no'] ?? '';
$cart           = $data['cart'] ?? [];

if (!$order_id || !$transaction_id || empty($cart)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$keyStmt = $conn->prepare("SELECT flutterwave_secret_key, notification_email, telegram_bot_token, telegram_chat_id FROM tenants WHERE id = ?");
$keyStmt->bind_param("i", $tenant_id);
$keyStmt->execute();
$keyRow = $keyStmt->get_result()->fetch_assoc();
$secretKey   = $keyRow['flutterwave_secret_key'] ?? '';
$notifyEmail = $keyRow['notification_email'] ?? '';
$botToken    = $keyRow['telegram_bot_token'] ?? '';
$chatId      = $keyRow['telegram_chat_id'] ?? '';

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Secret key not found"]);
    exit;
}

$isLocal = getenv('APP_ENV') === 'local';

// Production: SSL verification enabled (CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2)
// $curl = curl_init();
// curl_setopt_array($curl, [
//     CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
//     CURLOPT_RETURNTRANSFER => true,
//     CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secretKey"],
//     CURLOPT_SSL_VERIFYPEER => true,
//     CURLOPT_SSL_VERIFYHOST => 2,
// ]);

// Local: SSL verification disabled (AWebServer has no SSL certs bundle)
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $secretKey"],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
$curlCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if (curl_errno($curl)) {
    echo json_encode(["status" => "error", "message" => "Payment gateway error", "curl_error" => $curlError, "http_code" => $curlCode]);
    exit;
}
curl_close($curl);

$result = json_decode($response, true);

if (
    !$result ||
    ($result['status'] ?? '') !== 'success' ||
    ($result['data']['status'] ?? '') !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

$stmt = $conn->prepare("SELECT total_amount, status FROM paid_orders WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $order_id, $tenant_id);
$stmt->execute();
$stmt->bind_result($db_amount, $status);
$stmt->fetch();
$stmt->close();

if ($status === 'paid') {
    echo json_encode(["status" => "error", "message" => "Already paid"]);
    exit;
}

$flutter_amount = (float)$result['data']['amount'];
$db_amount      = (float)$db_amount;

if (abs($db_amount - $flutter_amount) > 0.01) {
    echo json_encode([
        "status"      => "error",
        "message"     => "Amount mismatch",
        "db"          => $db_amount,
        "flutterwave" => $flutter_amount
    ]);
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        UPDATE paid_orders
        SET status = 'paid', payment_ref = ?
        WHERE id = ? AND status = 'payment_pending' AND tenant_id = ?
    ");
    $stmt->bind_param("sii", $transaction_id, $order_id, $tenant_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Order update failed");
    }

    if ($orderType === 'table' && !empty($tableNo)) {
        $stmt2 = $conn->prepare("
            INSERT INTO booked_tables (tenant_id, table_id, booked)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE booked = 1
        ");
        $stmt2->bind_param("ii", $tenant_id, $tableNo);
        $stmt2->execute();
    }

    $stockStmt = $conn->prepare("
        UPDATE menu_stock 
        SET stock = stock - ?, 
            available = CASE WHEN stock - ? <= 0 THEN 0 ELSE 1 END
        WHERE menu_id = ? AND stock >= ? AND tenant_id = ?
    ");

    foreach ($cart as $item) {
        $qty = (int)$item['quantity'];
        $id  = (int)$item['id'];
        $stockStmt->bind_param("iiiii", $qty, $qty, $id, $qty, $tenant_id);
        $stockStmt->execute();

        if ($stockStmt->affected_rows === 0) {
            throw new Exception("Stock error: " . $item['name']);
        }
    }

    $conn->commit();

    $message = "
✅ *New Order Confirmed!*

🧾 *Order ID:* #{$order_id}
💳 *Transaction ID:* {$transaction_id}
💰 *Amount:* ₦" . number_format($flutter_amount, 2) . "
📦 *Order Type:* {$orderType}
" . ($tableNo ? "🪑 *Table:* {$tableNo}" : "") . "
";

    $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["chat_id" => $chatId, "text" => $message, "parse_mode" => "Markdown"]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);

    sendEmail(
        $notifyEmail,
        "New Order Confirmed — #" . $order_id,
        "
        <h2>✅ New Order Confirmed</h2>
        <p><b>Order ID:</b> #{$order_id}</p>
        <p><b>Transaction ID:</b> {$transaction_id}</p>
        <p><b>Amount:</b> ₦" . number_format($flutter_amount, 2) . "</p>
        <p><b>Order Type:</b> {$orderType}</p>
        " . ($tableNo ? "<p><b>Table:</b> {$tableNo}</p>" : "") . "
        "
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
