<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

session_start();

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input    = json_decode(file_get_contents("php://input"), true);
$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

// ── Step 1: Check admin credentials ──
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
    exit;
}

$admin = $res->fetch_assoc();

// ── Step 2: Check subscription status ──
$subStmt = $conn->prepare("
    SELECT status, trial_ends_at, renewal_date, plan, zara_credits
    FROM subscriptions
    WHERE LOWER(email) = LOWER(?)
    ORDER BY created_at DESC
    LIMIT 1
");
$subStmt->bind_param("s", $admin['email']);
$subStmt->execute();
$subRes = $subStmt->get_result();
$sub    = $subRes->fetch_assoc();

$now     = date("Y-m-d H:i:s");
$allowed = false;

if ($sub) {
    if ($sub['status'] === 'trial' && $sub['trial_ends_at'] > $now) {
        $allowed = true;
    } elseif ($sub['status'] === 'active' && $sub['renewal_date'] >= date("Y-m-d")) {
        $allowed = true;
    }
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode([
        "error"   => "subscription_expired",
        "message" => "Your subscription has expired. Please renew to continue.",
    ]);
    exit;
}

// ── Step 3: All good — start session ──
$_SESSION['admin_id']      = $admin['id'];
$_SESSION['admin_email']   = $admin['email'];
$_SESSION['last_activity'] = time();
$_SESSION['expire_time']   = 30 * 60;

echo json_encode([
    "success" => true,
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
