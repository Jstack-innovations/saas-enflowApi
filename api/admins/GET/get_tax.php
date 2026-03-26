<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";

$taxFile = __DIR__ . "/../../GET/JSON/tax.json";

if (!file_exists($taxFile)) {
    echo json_encode([
        "tax"=>0,
        "delivery_fee"=>0,
        "service_fee"=>0
    ]);
    exit;
}

$data = json_decode(file_get_contents($taxFile), true);

echo json_encode($data);
