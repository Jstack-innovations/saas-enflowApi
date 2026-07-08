<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tenant");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.category,
        m.name,
        m.description,
        m.price,
        m.image,
        m.tags,
        m.badge,
        COALESCE(s.stock, 0) AS stock,
        COALESCE(s.available, 0) AS available
    FROM menu_items m
    LEFT JOIN menu_stock s ON m.id = s.menu_id AND s.tenant_id = ?
    WHERE m.tenant_id = ?
    ORDER BY m.id ASC
");
$stmt->bind_param("ii", $tenant_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "query failed"]);
    exit;
}

$menu = [];
while ($row = $result->fetch_assoc()) {
    $category = $row["category"];
    $row["tags"] = json_decode($row["tags"], true) ?? [];
    unset($row["category"]);
    $menu[$category][] = $row;
}

echo json_encode($menu);