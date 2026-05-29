<?php
//require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id       = intval($data['id'] ?? 0);
$quantity = intval($data['quantity'] ?? 0);
$menu_id  = intval($data['menu_id'] ?? 0);

// 1. Mark order as done
$stmt = $conn->prepare("UPDATE kitchen_production SET status = 'done' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// 2. Add quantity to existing stock
$stmt = $conn->prepare("UPDATE menu_stock SET stock = stock + ? WHERE menu_id = ?");
$stmt->bind_param("ii", $quantity, $menu_id);
$stmt->execute();

echo json_encode(["success" => true]);
