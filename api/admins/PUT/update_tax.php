<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$taxFile = __DIR__ . "/../../GET/JSON/tax.json";

/* 🔥 SAFE JSON PARSE */
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

/* 🚨 PREVENT NULL ERROR */
if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid or missing JSON body"
    ]);
    exit;
}

$action = $data['action'] ?? "update";

$taxJson = [
    "tax" => 0,
    "delivery_fee" => 0,
    "service_fee" => 0
];

if ($action === "update") {

    $taxJson['tax'] = isset($data['tax']) ? floatval($data['tax']) : 0;
    $taxJson['delivery_fee'] = isset($data['delivery_fee']) ? floatval($data['delivery_fee']) : 0;
    $taxJson['service_fee'] = isset($data['service_fee']) ? floatval($data['service_fee']) : 0;
}

file_put_contents($taxFile, json_encode($taxJson, JSON_PRETTY_PRINT));

echo json_encode(["success" => true]);
