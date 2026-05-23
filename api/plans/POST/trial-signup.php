<?php
/**
 * Enflow Trial Signup API
 * Endpoint: POST /api/trial-signup
 *
 * Logic:
 *  - If email OR phone already exists in DB → return "existing" → frontend sends to /checkout
 *  - If neither exists → create new user, start trial → return "new" → frontend sends to /onboarding
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

require_once __DIR__ . '/../../SECURE/config.php';
// Remove this:
// define("TRIAL_DAYS", 5);

// Add this:
$setting = $pdo->query("SELECT setting_value FROM enflow_settings WHERE setting_key = 'trial_days' LIMIT 1")->fetch();
$trialDays = (int)($setting["setting_value"] ?? 5);

// ── Parse body ──
$body = json_decode(file_get_contents("php://input"), true);

$name  = trim($body["name"]  ?? "");
$email = trim(strtolower($body["email"] ?? ""));
$phone = trim($body["phone"] ?? "");
$plan  = trim($body["plan"]  ?? "");

// ── Validate ──
if (!$name || !$email || !$phone) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Name, email, and phone are required."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Please enter a valid email address."]);
    exit();
}

// Normalize phone — strip spaces, dashes, leading +234 → 0 prefix
$phone = preg_replace('/[\s\-\(\)]/', '', $phone);
if (str_starts_with($phone, "+234")) {
    $phone = "0" . substr($phone, 4);
}
if (!preg_match('/^0[7-9][01]\d{8}$/', $phone)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Please enter a valid Nigerian phone number."]);
    exit();
}

// ── Check if email OR phone already exists ──
$stmt = $pdo->prepare("SELECT id, name, email, phone, trial_started_at, trial_ends_at, is_subscribed FROM enflow_users WHERE email = :email OR phone = :phone LIMIT 1");
$stmt->execute([":email" => $email, ":phone" => $phone]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode([
        "status"  => "existing",
        "message" => "Account found. Please upgrade to continue.",
        "user"    => [
            "id"            => $existing["id"],
            "name"          => $existing["name"],
            "email"         => $existing["email"],
            "phone"         => $existing["phone"],
            "is_subscribed" => (bool) $existing["is_subscribed"],
            "trial_ends_at" => $existing["trial_ends_at"],
        ],
    ]);
    exit();
}

// ── New user — create account and start trial ──
$trialStart = date("Y-m-d H:i:s");
$trialEnd = date("Y-m-d H:i:s", strtotime("+" . $trialDays . " days"));
$token      = bin2hex(random_bytes(32));

try {
    $stmt = $pdo->prepare("
        INSERT INTO enflow_users (name, email, phone, selected_plan, trial_started_at, trial_ends_at, onboarding_token, is_subscribed, created_at)
        VALUES (:name, :email, :phone, :plan, :trial_start, :trial_end, :token, 0, NOW())
    ");
    $stmt->execute([
        ":name"        => $name,
        ":email"       => $email,
        ":phone"       => $phone,
        ":plan"        => $plan,
        ":trial_start" => $trialStart,
        ":trial_end"   => $trialEnd,
        ":token"       => $token,
    ]);

    $userId = $pdo->lastInsertId();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Could not create your account. Please try again."]);
    exit();
}

echo json_encode([
    "status"  => "new",
    "message" => "Trial started successfully.",
    "user"    => [
        "id"               => $userId,
        "name"             => $name,
        "email"            => $email,
        "phone"            => $phone,
        "selected_plan"    => $plan,
        "trial_starts_at"  => $trialStart,
        "trial_ends_at"    => $trialEnd,
        "trial_days" => $trialDays,
        "onboarding_token" => $token,
    ],
]);
