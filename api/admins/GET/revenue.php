<?php

// ===== CORS HEADERS =====
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../SECURE/db.php";

// Initialize last 7 days with zero revenue
$dailyRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyRevenue[$date] = 0;
}

// Fetch revenue from DB for the last 7 days
$sql = "
SELECT 
    DATE(created_at) as order_date,
    SUM(total_amount) as total
FROM paid_orders
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
GROUP BY DATE(created_at)
";

$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $dailyRevenue[$row['order_date']] = floatval($row['total']);
    }
}

// Format for frontend
$output = ["dailyRevenue" => []];

foreach ($dailyRevenue as $date => $total) {
    $dayName = date('D', strtotime($date));
    $output["dailyRevenue"][] = [
        "day" => $dayName,
        "revenue" => $total
    ];
}

echo json_encode($output);
