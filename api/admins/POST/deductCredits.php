<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";
require_once __DIR__ . "/../../SECURE/centralProxy.php";

$tenant_id = getTenantId($conn);
$adminId   = $GLOBALS['admin_id'];

$stmt = $conn->prepare("SELECT email FROM admins WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $adminId, $tenant_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$email = $admin["email"] ?? "";

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not authenticated."]);
    exit();
}

$data   = json_decode(file_get_contents("php://input"), true);
$amount = (int)($data["credits"] ?? 1);

$ch = curl_init(CENTRAL_SERVER . "/deductCredits");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 10,
    // CURLOPT_SSL_VERIFYPEER => true,  // production
    CURLOPT_SSL_VERIFYPEER => false,    // local
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode(["email" => $email, "credits" => $amount]),
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $res;