<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$data = [
    "users"         => [],
    "verifications" => []
];

/* USERS */
$stmt = $conn->prepare("SELECT id, full_name, email, phone, status, created_at FROM users WHERE tenant_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$q = $stmt->get_result();
while ($row = $q->fetch_assoc()) {
    $data["users"][] = $row;
}

/* VERIFICATIONS */
$stmt2 = $conn->prepare("
    SELECT lv.*, u.full_name, u.email
    FROM login_verifications lv
    LEFT JOIN users u ON u.id = lv.user_id AND u.tenant_id = ?
    WHERE lv.tenant_id = ?
    ORDER BY lv.id DESC
");
$stmt2->bind_param("ii", $tenant_id, $tenant_id);
$stmt2->execute();
$q2 = $stmt2->get_result();
while ($row = $q2->fetch_assoc()) {
    $data["verifications"][] = $row;
}

echo json_encode($data);