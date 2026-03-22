<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

session_start();

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
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$loggedIn = false;

if (isset($_SESSION['admin_id'])) {
    // Check session expiry
    if (isset($_SESSION['last_activity'], $_SESSION['expire_time']) &&
        (time() - $_SESSION['last_activity']) < $_SESSION['expire_time']) {
        $_SESSION['last_activity'] = time(); // update last activity
        $loggedIn = true;
    } else {
        session_unset();
        session_destroy();
    }
}

echo json_encode(["loggedIn" => $loggedIn]);
?>
