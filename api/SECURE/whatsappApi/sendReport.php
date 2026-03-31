<?php
require_once __DIR__ . "/../authGuard.php";

require __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

// ===== Twilio Credentials =====
$sid = "ACc9ad391bff43d4ce8463574671ab1be5";
$token = "9eed46d720ded0519127b7e28dcf38ea";
$fromWhatsapp = "whatsapp:+14155238886";
$toWhatsapp = "whatsapp:+2347089913116";

$client = new Client($sid, $token);

// ===== DB Connection =====
$file = __DIR__ . '/../db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file; // provides $conn (MySQLi)

$costPercentage = 0.65; // same as your dashboard

try {
    // SUM all paid orders — no date filter
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) AS grossRevenue
        FROM paid_orders
        WHERE status = 'paid'
    ");
    $stmt->execute();
    $stmt->bind_result($grossRevenue); // bind result to variable
    $stmt->fetch();                    // fetch the value
    $stmt->close();

    $grossRevenue = $grossRevenue ?? 0;
    $estimatedCost = $grossRevenue * $costPercentage;
    $estimatedProfit = $grossRevenue - $estimatedCost;
    $profitMargin = $grossRevenue ? round(($estimatedProfit / $grossRevenue) * 100) : 0;

    // Build message
    $message = "📊 Artisanè Grilluxxè Daily Business Report\n\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($grossRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($estimatedCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($estimatedProfit) . "\n";
    $message .= "📊 Profit Margin: {$profitMargin}%\n\n";
    $message .= "Generated automatically.";

    // Send WhatsApp
    $client->messages->create(
        $toWhatsapp,
        [
            "from" => $fromWhatsapp,
            "body" => $message
        ]
    );

    echo "Report sent successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
