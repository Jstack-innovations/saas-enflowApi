<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . "/authGuard.php";
require_once __DIR__ . '/tenant.php';

header("Content-Type: application/json");

$tenant_id = getTenantId($conn);

// Pull telegram credentials from tenants table
$stmt = $conn->prepare("SELECT telegram_bot_token, telegram_chat_id FROM tenants WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$stmt->bind_result($botToken, $chatId);
$stmt->fetch();
$stmt->close();

if (!$botToken || !$chatId) {
    echo json_encode(["success" => false, "message" => "Telegram not configured for this tenant"]);
    exit;
}

$costPercentage = 0.65;

try {
    // ===== Today's Paid Revenue =====
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) FROM paid_orders
        WHERE status = 'paid' AND DATE(created_at) = CURDATE() AND tenant_id = ?
    ");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->bind_result($todayRevenue);
    $stmt->fetch();
    $stmt->close();
    $todayRevenue = $todayRevenue ?? 0;

    // ===== All-Time Paid Revenue =====
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) FROM paid_orders
        WHERE status = 'paid' AND tenant_id = ?
    ");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->bind_result($allTimeRevenue);
    $stmt->fetch();
    $stmt->close();
    $allTimeRevenue = $allTimeRevenue ?? 0;

    // ===== All-Time Total Revenue (every status) =====
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) FROM paid_orders
        WHERE tenant_id = ?
    ");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->bind_result($totalRevenue);
    $stmt->fetch();
    $stmt->close();
    $totalRevenue = $totalRevenue ?? 0;

    // ===== Calculate =====
    $todayCost     = $todayRevenue   * $costPercentage;
    $todayProfit   = $todayRevenue   - $todayCost;
    $todayMargin   = $todayRevenue   ? round(($todayProfit   / $todayRevenue)   * 100) : 0;

    $allTimeCost   = $allTimeRevenue * $costPercentage;
    $allTimeProfit = $allTimeRevenue - $allTimeCost;
    $allTimeMargin = $allTimeRevenue ? round(($allTimeProfit / $allTimeRevenue) * 100) : 0;

    // ===== Build Message =====
    $date = date("F j, Y");

    $message  = "📊 *Artisanè Grills Business Report*\n";
    $message .= "🗓 {$date}\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    $message .= "📅 *TODAY'S PERFORMANCE*\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($todayRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($todayCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($todayProfit) . "\n";
    $message .= "📊 Profit Margin: {$todayMargin}%\n\n";

    $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";

    $message .= "🏆 *ALL-TIME PERFORMANCE*\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($allTimeRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($allTimeCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($allTimeProfit) . "\n";
    $message .= "📊 Profit Margin: {$allTimeMargin}%\n\n";

    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "🧾 *Total Revenue (All Time):* ₦" . number_format($totalRevenue) . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
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