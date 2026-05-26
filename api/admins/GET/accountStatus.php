<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

// Get email from the session record authGuard already validated
$stmt = $conn->prepare("SELECT email FROM admins WHERE id = ?");
$stmt->bind_param("i", $GLOBALS['admin_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    http_response_code(401);
    echo json_encode(["error" => "Admin not found"]);
    exit;
}

$email = $admin['email'];

// Proxy to central server
//$ch = curl_init("https://enflowsubscriptions.onrender.com/accountStatus");
// Proxy to central server
$ch = curl_init("https://enflowsubscriptions-production.up.railway.app/accountStatus");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode(["email" => $email]),
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false || $code === 0) {
    http_response_code(503);
    echo json_encode(["error" => "Service unavailable"]);
    exit;
}

http_response_code($code);
echo $res;
