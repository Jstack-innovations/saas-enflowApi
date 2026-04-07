<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
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
$amount       = $data['amount'] ?? 0;
$tx_id        = $data['transaction_id'] ?? '';

if (!$tx_id || !$email || !$amount) {
    echo json_encode(["status"=>"error","message"=>"Missing data"]);
    exit;
}



#################################################
# 🔐 FETCH SECRET KEY FROM flutterwave-key.php
#################################################

ob_start();
include __DIR__ . '/../../SECURE/flutterwave-key.php';
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



#################################################
# 🔐 VERIFY WITH FLUTTERWAVE SECRET KEY
#################################################

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$tx_id/verify",
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
    echo json_encode(["status"=>"error","message"=>"Payment not verified"]);
    exit;
}

#################################################
# ✅ OPTIONAL: CHECK AMOUNT MATCH
#################################################

if ((float)$result['data']['amount'] !== (float)$amount) {
    echo json_encode(["status"=>"error","message"=>"Amount mismatch"]);
    exit;
}



#################################################
# 🔑 GENERATE 21 DIGIT SUBSCRIPTION CODE
#################################################

function generateSubscriptionCode($length = 21) {
    $characters = '0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

$subscriptionCode = generateSubscriptionCode();




#################################################
# 💾 INSERT INTO DATABASE
#################################################

$stmt = $conn->prepare("
    INSERT INTO subscriptions
(fullname, username, email, phone, country, dob, gender,
 business_type, business_name, plan, amount, transaction_id, subscription_code, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$status = "active";

$stmt->bind_param(
    "ssssssssssdsss",  // 14 parameters: 13 strings/doubles + status
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
    $subscriptionCode, // <- bind the generated subscription code here
    $status
);

$stmt->execute();

echo json_encode([
    "status"=>"success",
    "subscription_code"=>$subscriptionCode
]);
