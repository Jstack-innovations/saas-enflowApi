<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/tenant.php";
require_once __DIR__ . "/../../SECURE/centralProxy.php";

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("SELECT email FROM admins WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $GLOBALS['admin_id'], $tenant_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    http_response_code(401);
    echo json_encode(["error" => "Admin not found"]);
    exit;
}

$email = $admin['email'];
//FOR PRODUCTION
//$ch = curl_init(CENTRAL_SERVER . "/accountStatus");

//FOR DEVELOPMENT
$ch = curl_init(CENTRAL_SERVER . "/api/plans/GET/CORS/accountStatus.php");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false, // local dev only — comment out in production
    CURLOPT_SSL_VERIFYHOST => false, // local dev only — comment out in production
    // CURLOPT_SSL_VERIFYPEER => true, // production
    // CURLOPT_SSL_VERIFYHOST => 2,    // production
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