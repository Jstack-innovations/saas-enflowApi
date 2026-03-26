<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$data = file_get_contents("php://input");

$filePath = __DIR__ . "/../../GET/JSON/offers.json";

file_put_contents($filePath, $data);

echo json_encode(["status" => "success"]);
?>
