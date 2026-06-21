<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }

// Call central server with this local server's URL as identifier
$isHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
    || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");

$localUrl = ($isHttps ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];


$ch = curl_init("https://enflowsubscriptions-production.up.railway.app/countdown");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["local_server_url" => $localUrl]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

//if (!$response || $httpCode !== 200) {
    //echo json_encode(["status" => "error", "message" => "Could not reach central server"]);
    //exit;
//}
if (!$response || $httpCode !== 200) {
    echo json_encode([
        "status" => "error", 
        "message" => "Could not reach central server",
        "http_code" => $httpCode,
        "response" => $response
    ]);
    exit;
}

echo $response;
