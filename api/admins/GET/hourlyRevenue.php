<?php

// ===== CORS =====
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../SECURE/db.php";

// Initialize 24 hours with 0 revenue
$hourlyRevenue = [];
for ($h = 0; $h < 24; $h++) {
    $hourlyRevenue[$h] = 0;
}

// Fetch today's revenue grouped by hour
$sql = "
SELECT 
    HOUR(created_at) as order_hour,
    SUM(total_amount) as total
FROM paid_orders
WHERE DATE(created_at) = CURDATE()
GROUP BY HOUR(created_at)
";

$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $hour = intval($row['order_hour']);
        $hourlyRevenue[$hour] = floatval($row['total']);
    }
}

// Format for frontend
$output = ["hourlyRevenue" => []];

foreach ($hourlyRevenue as $hour => $total) {

    // Convert 24hr to 12hr format
    $formattedHour = date("gA", strtotime($hour . ":00"));

    $output["hourlyRevenue"][] = [
        "hour" => $formattedHour,
        "revenue" => $total
    ];
}

echo json_encode($output);
