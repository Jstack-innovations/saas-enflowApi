<?php
require_once __DIR__ . "/authGuard.php";
require_once __DIR__ . "/centralProxy.php";

//FOR PRODUCTION
// $ch = curl_init(CENTRAL_SERVER . "/flutterwave");

//FOR LOCAL
$ch = curl_init(CENTRAL_SERVER . "/api/SECURE/flutterwave-key.php");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,

    // FOR PRODUCTION
    // CURLOPT_SSL_VERIFYPEER => true,
    // CURLOPT_SSL_VERIFYHOST => 2,

    // FOR LOCAL
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $res;