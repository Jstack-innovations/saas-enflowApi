<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

require_once __DIR__ . '/../../SECURE/config.php';

$setting   = $pdo->query("SELECT setting_value FROM enflow_settings WHERE setting_key = 'trial_days' LIMIT 1")->fetch();
$trialDays = (int)($setting["setting_value"] ?? 10);

$body  = json_decode(file_get_contents("php://input"), true);
$name  = trim($body["name"]  ?? "");
$email = trim(strtolower($body["email"] ?? ""));
$phone = trim($body["phone"] ?? "");
$plan  = trim($body["plan"]  ?? "");

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

// ── Check if email OR phone already exists in subscriptions ──
$stmt = $pdo->prepare("
    SELECT id, name, email, phone, status, trial_ends_at, renewal_date, plan
    FROM subscriptions 
    WHERE LOWER(email) = :email OR phone = :phone 
    LIMIT 1
");
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
            "status"        => $existing["status"],
            "trial_ends_at" => $existing["trial_ends_at"],
            "renewal_date"  => $existing["renewal_date"],
            "plan"          => $existing["plan"],
        ],
    ]);
    exit();
}

// ── New user — insert trial row into subscriptions ──
$trialStart       = date("Y-m-d H:i:s");
$trialEnd         = date("Y-m-d H:i:s", strtotime("+" . $trialDays . " days"));
$token            = bin2hex(random_bytes(32));
$subscriptionCode = "TRIAL-" . strtoupper(substr(md5(uniqid()), 0, 10));

try {
    $stmt = $pdo->prepare("
        INSERT INTO subscriptions (
            name, fullname, email, phone, plan,
            status, trial_started_at, trial_ends_at,
            onboarding_token, subscription_code,
            zara_credits, zara_credits_used, created_at
        )
        VALUES (
            :name, :name, :email, :phone, :plan,
            'trial', :trial_start, :trial_end,
            :token, :sub_code,
            :zara_credits, 0, NOW()
        )
    ");
    $stmt->execute([
        ":name"         => $name,
        ":email"        => $email,
        ":phone"        => $phone,
        ":plan"         => $plan,
        ":trial_start"  => $trialStart,
        ":trial_end"    => $trialEnd,
        ":token"        => $token,
        ":sub_code"     => $subscriptionCode,
        ":zara_credits" => 20, // trial credits
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
        "plan"             => $plan,
        "trial_starts_at"  => $trialStart,
        "trial_ends_at"    => $trialEnd,
        "trial_days"       => $trialDays,
        "onboarding_token" => $token,
        "subscription_code"=> $subscriptionCode,
    ],
]);
