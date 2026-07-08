<?php
function getTenantIdPDO($pdo): int {
    $slug = $_SERVER['HTTP_X_TENANT'] ?? '';

    if (!$slug) {
        http_response_code(400);
        echo json_encode(["error" => "Missing X-Tenant header"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        http_response_code(403);
        echo json_encode(["error" => "Tenant not found"]);
        exit;
    }

    return (int) $tenant['id'];
}
