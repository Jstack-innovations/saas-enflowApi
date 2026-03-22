<?php

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
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

$input = json_decode(file_get_contents("php://input"), true);

$id = $input["id"] ?? null;

if(!$id){
    echo json_encode(["success"=>false]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i",$id);

if($stmt->execute()){
    echo json_encode(["success"=>true]);
}else{
    echo json_encode(["success"=>false]);
}
