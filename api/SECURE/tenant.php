<?php
function getTenantId($conn): int {
    $slug = $_SERVER['HTTP_X_TENANT'] ?? '';

    if (!$slug) {
        http_response_code(400);
        echo json_encode(["error" => "Missing X-Tenant header"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM tenants WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();

    if (!$tenant) {
        http_response_code(403);
        echo json_encode(["error" => "Tenant not found"]);
        exit;
    }

    return (int) $tenant['id'];
}
