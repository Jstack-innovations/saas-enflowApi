<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$file = __DIR__ . '/../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;



// Read JSON payload from Flutterwave
$data = json_decode(file_get_contents("php://input"), true);

$tableId        = $data['tableId'] ?? null;
$name           = $data['name'] ?? null;
$email          = $data['email'] ?? null;
$phone          = $data['phone'] ?? null;
$bookingDate    = $data['bookingDate'] ?? null;
$amount         = $data['amount'] ?? null;
$transaction_id = $data['transaction_id'] ?? null;

// Validate required fields
if (!$tableId || !$name || !$email || !$phone || !$bookingDate || !$amount || !$transaction_id) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}



// Fetch Flutterwave secret key
ob_start();
include __DIR__ . '/../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();
$keyData = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode([
        "success" => false,
        "message" => "Secret key not found"
    ]);
    exit;
}

// Verify transaction with Flutterwave
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$transaction_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if ($result['status'] !== 'success' || $result['data']['status'] !== 'successful') {
    echo json_encode([
        "success" => false,
        "message" => "Payment not verified"
    ]);
    exit;
}

// Optional: verify amount
if ((float)$result['data']['amount'] !== (float)$amount) {
    echo json_encode([
        "success" => false,
        "message" => "Amount mismatch"
    ]);
    exit;
}




// ✅ FIX ADDED HERE — Convert booking date to proper MySQL DATETIME format
$bookingDate = date("Y-m-d H:i:s", strtotime($bookingDate));

// Generate unique reservation code
$reservation_code = "RES-ART-" . strtoupper(substr(md5(uniqid()), 0, 8));

// Mark table as booked
$stmt = $conn->prepare("
    INSERT INTO booked_tables (table_id, booked)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE booked = 1
");
$stmt->bind_param("i", $tableId);
$stmt->execute();

// Save reservation details
$stmt2 = $conn->prepare("
    INSERT INTO reservations 
    (table_id, name, email, phone, booking_date, amount, transaction_id, status, reservation_code)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
");
// ✅ FIX: Correct type string for bind_param
$stmt2->bind_param("issssdss", $tableId, $name, $email, $phone, $bookingDate, $amount, $transaction_id, $reservation_code);
$stmt2->execute();

$reservation_id = $stmt2->insert_id;

if ($reservation_id) {
    echo json_encode([
        "success" => true,
        "reservation_id" => $reservation_id,
        "reservation_code" => $reservation_code
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Booking failed"
    ]);
}
