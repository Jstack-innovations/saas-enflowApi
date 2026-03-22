<?php
// flutterwave-key.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// You can store this in environment variables or a config file
$publicKey = getenv('FLUTTERWAVE_PUBLIC_KEY') ?: 'FLWPUBK_TEST-02a9fcd2b494145c0ae3921c89e834d0-X';

echo json_encode([
    'publicKey' => $publicKey
]);
