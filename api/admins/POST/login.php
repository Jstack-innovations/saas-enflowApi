<?php
// No session needed anymore
$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file;
$conn->set_charset("latin1");

$allowedOrigins = [
    "http://localhost:5173",
    "https://artisangrills-production.up.railway.app",
    "https://admin-artisangrilluxe.vercel.app"
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$input    = json_decode(file_get_contents("php://input"), true);
$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

// Step 1: Look up by email
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}
$admin = $res->fetch_assoc();

// Step 1b: Verify password against stored bcrypt hash
if (!password_verify($password, $admin['password'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

// Step 2: Verify subscription on central server
//$ch = curl_init("https://enflowsubscriptions.onrender.com/verifyAccess");
// Step 2: Verify subscription on central server
$ch = curl_init("https://enflowsubscriptions-production.up.railway.app/verifyAccess");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode(["email" => $admin['email']]),
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

// Step 3: Generate token and save to DB
$token     = bin2hex(random_bytes(32)); // 64-char secure token
$expiresAt = date("Y-m-d H:i:s", time() + 30 * 60); // 30 min

$stmt2 = $conn->prepare(
    "INSERT INTO admin_sessions (admin_id, token, last_activity, expires_at)
     VALUES (?, ?, NOW(), ?)"
);
$stmt2->bind_param("iss", $admin['id'], $token, $expiresAt);
$stmt2->execute();

// Step 4: Return token to browser
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
?>
