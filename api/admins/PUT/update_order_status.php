<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

require_once __DIR__ . '/../../SECURE/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? '';

$stmt = $pdo->prepare("
    UPDATE paid_orders 
    SET order_status = ? 
    WHERE order_id = ?
");

$stmt->execute([$status, $id]);

    echo json_encode(['success' => true]);
}
