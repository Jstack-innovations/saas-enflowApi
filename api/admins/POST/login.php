<?php
// Session cookie for HTTPS
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true, // Vercel is HTTPS
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();

// Allow your Vercel frontends
$allowedOrigins = [
    "https://admin-artisangrilluxe.vercel.app",
    "https://artisangrills-production.up.railway.app"
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database
require_once __DIR__ . '/../../SECURE/db.php';
$conn->set_charset("utf8mb4"); // match DB

// Read input
$input = json_decode(file_get_contents("php://input"), true);
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password required"]);
    exit;
}

// Check credentials (plain text for now)
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $admin = $res->fetch_assoc();
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['expire_time'] = 30 * 60;

    echo json_encode(["success" => true, "admin" => $admin]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
}
