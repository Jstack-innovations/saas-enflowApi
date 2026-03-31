<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require __DIR__ . '/vendor/autoload.php';

use Twilio\Rest\Client;

$sid = "ACc9ad391bff43d4ce8463574671ab1be5";
$token = "9eed46d720ded0519127b7e28dcf38ea";

$client = new Client($sid, $token);

$grossRevenue = 1250000;
$estimatedCost = 812500;
$estimatedProfit = 437500;
$profitMargin = 35;

$message = "📊 Artisanè Grilluxxè Daily Business Report

💰 Gross Revenue: ₦" . number_format($grossRevenue) . "
📉 Estimated Cost: ₦" . number_format($estimatedCost) . "
📈 Estimated Profit: ₦" . number_format($estimatedProfit) . "
📊 Profit Margin: {$profitMargin}%

Generated automatically.";

$client->messages->create(
    "whatsapp:+2347089913116",
    [
        "from" => "whatsapp:+14155238886",
        "body" => $message
    ]
);

echo "Report sent successfully!";
