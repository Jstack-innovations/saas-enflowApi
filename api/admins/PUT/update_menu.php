<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(["success" => false, "error" => "Invalid or missing JSON body"]);
    exit;
}

$action   = $data['action'] ?? null;
$category = $data['category'] ?? null;

if (!$action || !$category) {
    echo json_encode(["success" => false, "error" => "Missing action or category"]);
    exit;
}

/* ── ADD ── */
if ($action === "add") {

    $name        = $data['name']        ?? "";
    $description = $data['description'] ?? "";
    $price       = floatval($data['price'] ?? 0);
    $image       = $data['image']       ?? "";
    $tags        = json_encode($data['tags'] ?? []);
    $badge       = $data['badge']       ?? "";
    $stock       = intval($data['stock'] ?? 0);
    $available   = filter_var($data['available'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO menu_items (tenant_id, category, name, description, price, image, tags, badge)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssdsss", $tenant_id, $category, $name, $description, $price, $image, $tags, $badge);
    $stmt->execute();
    $newId = $conn->insert_id;

    $stmt2 = $conn->prepare("
        INSERT INTO menu_stock (tenant_id, menu_id, stock, available)
        VALUES (?, ?, ?, ?)
    ");
    $stmt2->bind_param("iiii", $tenant_id, $newId, $stock, $available);
    $stmt2->execute();

    echo json_encode(["success" => true, "id" => $newId]);
    exit;
}

/* ── UPDATE ── */
if ($action === "update") {

    $id          = intval($data['id'] ?? 0);
    $name        = $data['name']        ?? "";
    $description = $data['description'] ?? "";
    $price       = floatval($data['price'] ?? 0);
    $image       = $data['image']       ?? "";
    $tags        = json_encode($data['tags'] ?? []);
    $badge       = $data['badge']       ?? "";
    $stock       = intval($data['stock'] ?? 0);
    $available   = filter_var($data['available'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE menu_items
        SET category = ?, name = ?, description = ?, price = ?, image = ?, tags = ?, badge = ?
        WHERE id = ? AND tenant_id = ?
    ");
    $stmt->bind_param("sssdsssii", $category, $name, $description, $price, $image, $tags, $badge, $id, $tenant_id);
    $stmt->execute();

    $stmt2 = $conn->prepare("
        UPDATE menu_stock SET stock = ?, available = ?
        WHERE menu_id = ? AND tenant_id = ?
    ");
    $stmt2->bind_param("iiii", $stock, $available, $id, $tenant_id);
    $stmt2->execute();

    echo json_encode(["success" => true]);
    exit;
}

/* ── DELETE ── */
if ($action === "delete") {

    $id = intval($data['id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $id, $tenant_id);
    $stmt->execute();

    $stmt2 = $conn->prepare("DELETE FROM menu_stock WHERE menu_id = ? AND tenant_id = ?");
    $stmt2->bind_param("ii", $id, $tenant_id);
    $stmt2->execute();

    echo json_encode(["success" => true]);
    exit;
}

/* ── PUSH TO KITCHEN ── */
if ($action === "push_to_kitchen") {

    $id       = intval($data['id']       ?? 0);
    $name     = $data['name']            ?? "";
    $quantity = intval($data['quantity'] ?? 0);
    $note     = $data['note']            ?? "";

    $stmt = $conn->prepare("
        INSERT INTO kitchen_production (tenant_id, menu_id, menu_name, category, quantity, note)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissis", $tenant_id, $id, $name, $category, $quantity, $note);
    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["success" => false, "error" => "Unknown action"]);