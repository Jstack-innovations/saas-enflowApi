<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

// DB connection (EDIT THIS)
$conn = new mysqli("localhost", "root", "", "your_database");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// 1. Load menu JSON
$file = __DIR__ . "/../JSON/menu.json";

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(["error" => "menu not found"]);
    exit;
}

$menu = json_decode(file_get_contents($file), true);

// 2. Load stock from DB
$stockResult = $conn->query("SELECT menu_id, stock, available FROM menu_stock");

$stockMap = [];

while ($row = $stockResult->fetch_assoc()) {
    $stockMap[$row["menu_id"]] = [
        "stock" => (int)$row["stock"],
        "available" => (int)$row["available"]
    ];
}

// 3. Merge stock into menu
foreach ($menu as $category => &$items) {
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

// 4. Return merged result
echo json_encode($menu);
