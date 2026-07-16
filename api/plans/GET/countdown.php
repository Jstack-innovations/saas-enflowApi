<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tenant");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }

require_once __DIR__ . "/../../SECURE/db.php";
require_once __DIR__ . "/../../SECURE/centralProxy.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$isHttps = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
    || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");

//FOR PRODUCTION
//$localUrl = ($isHttps ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];

//FOR LOCAL
$localUrl = ($isHttps ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . "/saas-enflowApi";

//FOR PRODUCTION
// $ch = curl_init(CENTRAL_SERVER . "/countdown");

//FOR LOCAL
$ch = curl_init(CENTRAL_SERVER . "/api/plans/POST/countdown.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "local_server_url" => $localUrl,
    "tenant_id"        => $tenant_id
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // local only — disable in production
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // production — uncomment when deploying
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode !== 200) {
    echo json_encode([
        "status"    => "error",
        "message"   => "Could not reach central server",
        "http_code" => $httpCode,
        "response"  => $response
    ]);
    exit;
}

echo $response;