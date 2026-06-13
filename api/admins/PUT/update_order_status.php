<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . '/../../SECURE/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $putData);
    $id = $putData['id'] ?? '';
    $status = $putData['status'] ?? '';

    $stmt = $pdo->prepare("UPDATE paid_orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$status, (int)$id]);

    echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
}
