<?php
// authGuard.php

// ---- SESSION CONFIG ----
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,     // only send over HTTPS
    'httponly' => true,   // not accessible via JS
    'samesite' => 'None'
]);
session_start();

// ---- CORS ----
$allowedOrigins = [
    "http://localhost:5173",
    "https://artisangrills-production.up.railway.app",
    "https://artisangrills-production.up.railway.app",
    "https://admin-artisangrilluxe.vercel.app"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// ---- Handle Preflight ----
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ---- AUTH GUARD ----
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// ---- SESSION EXPIRY CHECK ----
if (!isset($_SESSION['last_activity'], $_SESSION['expire_time']) ||
    (time() - $_SESSION['last_activity']) > $_SESSION['expire_time']
) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(["error" => "Session expired"]);
    exit;
}

// ---- REFRESH SESSION ACTIVITY ----
$_SESSION['last_activity'] = time();
