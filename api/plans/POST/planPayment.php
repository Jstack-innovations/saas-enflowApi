<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../SECURE/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$fullname     = $data['fullname'] ?? '';
$username     = $data['username'] ?? '';
$email        = $data['email'] ?? '';
$phone        = $data['phone'] ?? '';
$country      = $data['country'] ?? '';
$dob          = $data['dob'] ?? '';
$gender       = $data['gender'] ?? '';
$businessType = $data['businessType'] ?? '';
$businessName = $data['businessName'] ?? '';
$plan         = $data['plan'] ?? '';
$tx_id        = $data['transaction_id'] ?? '';

if (!$fullname || !$email || !$phone || !$plan || !$tx_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

/* ===== GET SECRET KEY ===== */
ob_start();
include __DIR__ . '/../../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();

$keyData   = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Secret key not found"]);
    exit;
}

/* ===== VERIFY PAYMENT ===== */
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$tx_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => "GET",
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ],
]);

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

/* ===== TRUST FLUTTERWAVE AMOUNT ===== */
$amount = (float)$result['data']['amount'];

/* ===== DUPLICATE CHECK ===== */
$dup = $conn->prepare("SELECT id FROM subscriptions WHERE transaction_id = ?");
$dup->bind_param("s", $tx_id);
$dup->execute();
$dup->store_result();

if ($dup->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Already processed"]);
    exit;
}

/* ===== GENERATE SUB CODE ===== */
$subscriptionCode = "SUB-" . strtoupper(substr(md5(uniqid()), 0, 10));

/* ===== RENEWAL DATE ===== */
if (stripos($plan, "annual") !== false) {
    $renewalDate = date("Y-m-d", strtotime("+1 year"));
} else {
    $renewalDate = date("Y-m-d", strtotime("+1 month"));
}

/* ===== ZARA CREDITS ===== */
if (stripos($plan, "annual") !== false) {
    $zaraCredits = 1500;
} else {
    $zaraCredits = 100;
}

/* ===== INSERT ===== */
$status = "active";

$stmt = $conn->prepare("
    INSERT INTO subscriptions (
        fullname,
        username,
        email,
        phone,
        country,
        dob,
        gender,
        business_type,
        business_name,
        plan,
        amount,
        transaction_id,
        subscription_code,
        status,
        renewal_date,
        zara_credits
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssssssdssssi",
    $fullname,
    $username,
    $email,
    $phone,
    $country,
    $dob,
    $gender,
    $businessType,
    $businessName,
    $plan,
    $amount,
    $tx_id,
    $subscriptionCode,
    $status,
    $renewalDate,
    $zaraCredits
);

if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
    exit;
}

echo json_encode([
    "status"            => "success",
    "subscription_code" => $subscriptionCode,
    "renewal_date"      => $renewalDate,
    "zara_credits"      => $zaraCredits,
]);
