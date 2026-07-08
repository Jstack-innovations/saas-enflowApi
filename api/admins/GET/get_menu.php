<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

/* ===== FETCH MENU + STOCK FROM DB ===== */
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

$menuJson = [];
while ($row = $result->fetch_assoc()) {
    $category = $row['category'];
    $row['tags'] = json_decode($row['tags'], true) ?? [];
    unset($row['category']);
    $menuJson[$category][] = $row;
}

echo json_encode(["menu" => $menuJson]);