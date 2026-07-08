<?php
require_once __DIR__ . "/authGuard.php";
require_once __DIR__ . "/centralProxy.php";

$ch = curl_init(CENTRAL_SERVER . "/flutterwave");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code);
echo $res;
