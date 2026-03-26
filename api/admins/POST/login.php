<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
// Fix charset to match table (latin1)
$conn->set_charset("latin1");

// Read input
$input = json_decode(file_get_contents("php://input"), true);
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

// Check credentials
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $admin = $res->fetch_assoc();
    
    // Set session
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['expire_time'] = 30 * 60; // 30 min

    echo json_encode(["success" => true, "admin" => $admin]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid email or password"]);
}
?>
