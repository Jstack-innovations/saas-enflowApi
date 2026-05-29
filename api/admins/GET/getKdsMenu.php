<?php

//require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/db.php";

$menuFile = __DIR__ . "/../../GET/JSON/menu.json";

$menuJson = json_decode(file_get_contents($menuFile), true);

/* ===== ADD STOCK ONLY ===== */

$stockResult = $conn->query("SELECT menu_id, stock, available FROM menu_stock");

$stockMap = [];

while ($row = $stockResult->fetch_assoc()) {
    $stockMap[$row["menu_id"]] = [
        "stock" => (int)$row["stock"],
        "available" => (int)$row["available"]
    ];
}

/* merge into existing menu */
foreach ($menuJson as $category => &$items) {
    foreach ($items as &$item) {

        $id = $item["id"];

        if (isset($stockMap[$id])) {
            $item["stock"] = $stockMap[$id]["stock"];
            $item["available"] = $stockMap[$id]["available"];
        } else {
            $item["stock"] = 0;
            $item["available"] = 0;
        }
    }
}

echo json_encode([
    "menu" => $menuJson
]);
