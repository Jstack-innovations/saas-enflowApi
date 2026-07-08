<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . '/../../SECURE/config.php';
require_once __DIR__ . '/../../SECURE/tenant_pdo.php';

$tenant_id = getTenantIdPDO($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    parse_str(file_get_contents("php://input"), $putData);

    $id     = $putData['id']     ?? '';
    $status = $putData['status'] ?? '';

    $stmt = $pdo->prepare("
        UPDATE paid_orders
        SET order_status = ?
        WHERE id = ? AND tenant_id = ?
    ");

    $stmt->execute([$status, $id, $tenant_id]);

    echo json_encode([
        'success' => true,
        'rows'    => $stmt->rowCount()
    ]);
}