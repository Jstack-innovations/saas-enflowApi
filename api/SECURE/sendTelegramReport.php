<?php
require_once __DIR__ . "/../authGuard.php";

header("Content-Type: application/json");

// ===== Telegram Credentials =====
$botToken = getenv("TELEGRAM_BOT_TOKEN");
$chatId   = getenv("TELEGRAM_CHAT_ID");

// ===== DB Connection =====
$file = __DIR__ . '/./db.php';
if (!file_exists($file)) die(json_encode(["error" => "db.php not found"]));
require_once $file;

$costPercentage = 0.65;

try {
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) AS grossRevenue
        FROM paid_orders
        WHERE status = 'paid'
    ");
    $stmt->execute();
    $stmt->bind_result($grossRevenue);
    $stmt->fetch();
    $stmt->close();

    $grossRevenue    = $grossRevenue ?? 0;
    $estimatedCost   = $grossRevenue * $costPercentage;
    $estimatedProfit = $grossRevenue - $estimatedCost;
    $profitMargin    = $grossRevenue ? round(($estimatedProfit / $grossRevenue) * 100) : 0;

    // ===== Same format as your WhatsApp message =====
    $message = "📊 *Artisanè Grills Daily Business Report*\n\n";
    $message .= "💰 *Gross Revenue:* ₦" . number_format($grossRevenue) . "\n";
    $message .= "📉 *Estimated Cost:* ₦" . number_format($estimatedCost) . "\n";
    $message .= "📈 *Estimated Profit:* ₦" . number_format($estimatedProfit) . "\n";
    $message .= "📊 *Profit Margin:* {$profitMargin}%\n\n";
    $message .= "🤖 _Generated automatically by Enflow._";

    // ===== Send to Telegram =====
    $url     = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $payload = http_build_query([
        "chat_id"    => $chatId,
        "text"       => $message,
        "parse_mode" => "Markdown"
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($result["ok"]) {
        echo json_encode(["success" => true, "message" => "Report sent!"]);
    } else {
        echo json_encode(["success" => false, "message" => $result["description"]]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
