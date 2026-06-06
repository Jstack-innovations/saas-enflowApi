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

$session_code   = $data['session_code']   ?? '';
$ref            = $data['transaction_id'] ?? '';
$total          = $data['amount']         ?? 0; // kept but no longer trusted



/* ===== VALIDATION ===== */

if (!$session_code || !$ref) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}




ob_start();
include __DIR__ . '/../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();
$keyData   = json_decode($keyOutput, true);
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
  CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$ref/verify",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST  => "GET",
  CURLOPT_HTTPHEADER     => array(
    "Authorization: Bearer $secretKey",
    "Content-Type: application/json"
  ),
));

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["status" => "error", "message" => "Payment gateway error"]);
    exit;
}

curl_close($curl);

$result = json_decode($response, true);

if (
    !$result ||
    $result['status'] !== 'success' ||
    $result['data']['status'] !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}


/* ===== FETCH ORDER FROM DB (SOURCE OF TRUTH) ===== */

$stmt = $conn->prepare("
    SELECT id, total_amount, status, payment_ref
    FROM paid_orders
    WHERE session_code = ?
    LIMIT 1
");

$stmt->bind_param("s", $session_code);
$stmt->execute();
$dbResult = $stmt->get_result();
$order = $dbResult->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(["status" => "error", "message" => "Session not found"]);
    exit;
}

/* ===== PREVENT DOUBLE PAYMENT ===== */

if ($order['status'] === 'paid') {
    echo json_encode(["status" => "error", "message" => "Already paid"]);
    exit;
}

/* ===== VERIFY TX_REF MATCHES SESSION ===== */

if (($result['data']['tx_ref'] ?? '') !== $session_code) {
    echo json_encode(["status" => "error", "message" => "Invalid reference"]);
    exit;
}

/* ===== AMOUNT CHECK (DB vs FLUTTERWAVE) ===== */

$flutter_amount = (float)$result['data']['amount'];
$db_amount      = (float)$order['total_amount'];

/* allow tiny rounding differences */
if (abs($flutter_amount - $db_amount) > 0.01) {
    echo json_encode(["status" => "error", "message" => "Amount mismatch"]);
    exit;
}




$conn->begin_transaction();

try {

    /* ===== UPDATE ORDER: open → paid ===== */
    /* Kitchen gets final confirmation */

    $updateStmt = $conn->prepare("
        UPDATE paid_orders
        SET status = 'paid', payment_ref = ?
        WHERE session_code = ? AND status = 'open'
    ");

    $updateStmt->bind_param("ss", $ref, $session_code);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("Session not found or already closed");
    }

    /* ===== GET ORDER ID FOR SUCCESS PAGE ===== */

    $fetchStmt = $conn->prepare("
        SELECT id FROM paid_orders WHERE session_code = ? LIMIT 1
    ");

    $fetchStmt->bind_param("s", $session_code);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    $fetchRow    = $fetchResult->fetch_assoc();
    $order_id    = $fetchRow['id'];

    $conn->commit();

    // ===== TELEGRAM =====
$botToken = getenv("TELEGRAM_BOT_TOKEN");
$chatId   = getenv("TELEGRAM_CHAT_ID");

$message = "✅ *Session Closed — Payment Confirmed!*\n\n🧾 *Order ID:* #{$order_id}\n💳 *Transaction ID:* {$ref}\n💰 *Amount:* ₦" . number_format($flutter_amount, 2) . "\n🔑 *Session:* {$session_code}";

$ch = curl_init("https://api.telegram.org/bot{$botToken}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["chat_id" => $chatId, "text" => $message, "parse_mode" => "Markdown"]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// ===== EMAIL =====
require_once __DIR__ . '/../SECURE/gmailApi/resend_mailer.php';
sendEmail(
    "wsamson630@gmail.com",
    "Session Closed — Order #{$order_id}",
    "<h2>✅ Session Closed</h2>
    <p><b>Order ID:</b> #{$order_id}</p>
    <p><b>Transaction ID:</b> {$ref}</p>
    <p><b>Amount:</b> ₦" . number_format($flutter_amount, 2) . "</p>
    <p><b>Session Code:</b> {$session_code}</p>"
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
