<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";

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
    "badge" => $data['badge'] ?? "",
    "available" => filter_var($data['available'] ?? true, FILTER_VALIDATE_BOOLEAN),
    "stock" => intval($data['stock'] ?? 0)
];
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
                "badge" => $data['badge'] ?? "",
                "available" => filter_var($data['available'] ?? true, FILTER_VALIDATE_BOOLEAN),
                "stock" => intval($data['stock'] ?? 0)
            ];

            break;
        }
    }
}

if ($action === "delete") {

    $id = intval($data['id'] ?? 0);

    foreach ($menuJson[$category] as $index => $item) {

        if ($item['id'] == $id) {
            array_splice($menuJson[$category], $index, 1);
            break;
        }
    }
}

file_put_contents($menuFile, json_encode($menuJson, JSON_PRETTY_PRINT));

echo json_encode(["success" => true]);
