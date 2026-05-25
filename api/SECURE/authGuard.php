<?php
$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file;

$allowedOrigins = [
    "http://localhost:5173",
    "https://artisangrills-production.up.railway.app",
    "https://artisangrills.onrender.com",
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// Extract token from Authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
$token = trim(substr($authHeader, 7));

// Validate token in DB
$stmt = $conn->prepare(
    "SELECT * FROM admin_sessions WHERE token = ? AND expires_at > NOW()"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    http_response_code(401);
    echo json_encode(["error" => "Session expired or invalid"]);
    exit;
}

// Refresh last_activity and extend expiry (rolling 30-min window)
$newExpiry = date("Y-m-d H:i:s", time() + 30 * 60);
$stmt2 = $conn->prepare(
    "UPDATE admin_sessions SET last_activity = NOW(), expires_at = ? WHERE token = ?"
);
$stmt2->bind_param("ss", $newExpiry, $token);
$stmt2->execute();

// Expose admin_id for downstream files
$GLOBALS['admin_id'] = $session['admin_id'];
