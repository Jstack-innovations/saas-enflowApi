<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";

$menuFile = __DIR__ . "/../../GET/JSON/menu.json";

$menuJson = json_decode(file_get_contents($menuFile), true);

/* 🔥 SAFE INPUT PARSING */
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

/* 🚨 STOP CRASH HERE */
if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid or missing JSON body"
    ]);
    exit;
}

$action = $data['action'] ?? null;
$category = $data['category'] ?? null;

if (!$action || !$category) {
    echo json_encode([
        "success" => false,
        "error" => "Missing action or category"
    ]);
    exit;
}

if ($action === "add") {

    $maxId = 0;

    foreach ($menuJson as $cat) {
        foreach ($cat as $item) {
            if ($item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
    }

    $newId = $maxId + 1;

    $menuJson[$category][] = [
        "id" => $newId,
        "name" => $data['name'] ?? "",
        "description" => $data['description'] ?? "",
        "price" => floatval($data['price'] ?? 0),
        "image" => $data['image'] ?? "",
        "tags" => $data['tags'] ?? [],
        "badge" => $data['badge'] ?? ""
        // ❌ removed stock + available from JSON (handled in DB)
    ];

    /* ✅ INSERT STOCK + AVAILABLE INTO DB */
    $stock = intval($data['stock'] ?? 0);
    $available = filter_var($data['available'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO menu_stock (menu_id, stock, available)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iii", $newId, $stock, $available);
    $stmt->execute();
}

if ($action === "update") {

    $id = intval($data['id'] ?? 0);

    foreach ($menuJson[$category] as $index => $item) {

        if ($item['id'] == $id) {

            $menuJson[$category][$index] = [
                "id" => $id,
                "name" => $data['name'] ?? "",
                "description" => $data['description'] ?? "",
                "price" => floatval($data['price'] ?? 0),
                "image" => $data['image'] ?? "",
                "tags" => $data['tags'] ?? [],
                "badge" => $data['badge'] ?? ""
                // ❌ removed stock + available from JSON
            ];

            break;
        }
    }

    /* ✅ UPDATE STOCK + AVAILABLE IN DB ONLY */
    $stock = intval($data['stock'] ?? 0);
    $available = filter_var($data['available'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE menu_stock 
        SET stock = ?, available = ? 
        WHERE menu_id = ?
    ");

    $stmt->bind_param("iii", $stock, $available, $id);
    $stmt->execute();
}

if ($action === "delete") {

    $id = intval($data['id'] ?? 0);

    foreach ($menuJson[$category] as $index => $item) {

        if ($item['id'] == $id) {
            array_splice($menuJson[$category], $index, 1);
            break;
        }
    }

    /* OPTIONAL: clean DB too */
    $stmt = $conn->prepare("DELETE FROM menu_stock WHERE menu_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}


//PUSH TO KITCHEN
if ($action === "push_to_kitchen") {

    $id       = intval($data['id'] ?? 0);
    $name     = $data['name'] ?? "";
    $quantity = intval($data['quantity'] ?? 0);
    $note     = $data['note'] ?? "";

    $stmt = $conn->prepare("
        INSERT INTO kitchen_production (menu_id, menu_name, category, quantity, note)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issis", $id, $name, $category, $quantity, $note);
    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}




file_put_contents($menuFile, json_encode($menuJson, JSON_PRETTY_PRINT));

echo json_encode(["success" => true]);
