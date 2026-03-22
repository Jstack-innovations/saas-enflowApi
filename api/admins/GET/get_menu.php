<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

session_start();

$menuFile = __DIR__ . "/../../GET/JSON/menu.json";

$menuJson = json_decode(file_get_contents($menuFile), true);

echo json_encode([
    "menu" => $menuJson
]);
