<?php
$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';
require_once __DIR__ . '/../../SECURE/centralProxy.php';
$conn->set_charset("latin1");

$allowedOrigins = [
    "http://localhost:5173",
    "https://artisangrills-production.up.railway.app",
    "https://admin-de-arinas-pot.getenflowai.online",
    "https://app.getenflowai.online"
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$tenant_id = getTenantId($conn);

$input    = json_decode(file_get_contents("php://input"), true);
$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND tenant_id = ?");
$stmt->bind_param("si", $email, $tenant_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}
$admin = $res->fetch_assoc();

if (!password_verify($password, $admin['password'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

// Verify subscription on central server

//FOR PRODUCTION
// $ch = curl_init(CENTRAL_SERVER . "/verifyAccess");

//FOR LOCAL
$ch = curl_init(CENTRAL_SERVER . "/api/plans/POST/verifyAccess.php");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode(["email" => $admin['email']]),
    CURLOPT_SSL_VERIFYPEER => false, // local only — disable in production
    // CURLOPT_SSL_VERIFYPEER => true, // production — uncomment when deploying
]);
$curlRes  = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$sub = json_decode($curlRes, true);

if ($httpCode !== 200 || ($sub['status'] ?? '') !== 'active') {
    http_response_code(403);
    echo json_encode([
        "error"   => "subscription_expired",
        "message" => "Your subscription has expired. Please renew to continue.",
    ]);
    exit;
}

$token     = bin2hex(random_bytes(32));
$expiresAt = date("Y-m-d H:i:s", time() + 30 * 60);

$stmt2 = $conn->prepare("
    INSERT INTO admin_sessions (tenant_id, admin_id, token, last_activity, expires_at)
    VALUES (?, ?, ?, NOW(), ?)
");
$stmt2->bind_param("iiss", $tenant_id, $admin['id'], $token, $expiresAt);
$stmt2->execute();

echo json_encode([
    "success" => true,
    "token"   => $token,
    "admin"   => $admin,
    "subscription" => [
        "status"        => $sub['status'],
        "plan"          => $sub['plan'],
        "zara_credits"  => $sub['zara_credits'],
        "renewal_date"  => $sub['renewal_date'],
        "trial_ends_at" => $sub['trial_ends_at'],
    ]
]);
