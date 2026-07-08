<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . '/../../SECURE/config.php';
require_once __DIR__ . '/../../SECURE/tenant_pdo.php';

$tenant_id = getTenantIdPDO($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $plate = $_GET['tracking_number'] ?? '';

    $stmt = $pdo->prepare("
        SELECT name, phone, total_amount, order_status 
        FROM paid_orders 
        WHERE plate_order_no = ? AND tenant_id = ?
    ");
    $stmt->execute([$plate, $tenant_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($order ?: []);
}