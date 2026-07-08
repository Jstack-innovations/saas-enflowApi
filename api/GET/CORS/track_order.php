<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tenant");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . '/../../SECURE/config.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "config.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant_pdo.php';

$tenant_id = getTenantIdPDO($pdo);

$order_number = trim($_GET['order_number'] ?? '');

$response = [
    'error'         => '',
    'order'         => null,
    'items'         => [],
    'status'        => '',
    'full_address'  => '',
    'recipient_lat' => null,
    'recipient_lng' => null
];

function reverseGeocodeOSM($lat, $lng) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng";
    $context = stream_context_create([
        "http" => ["header" => "User-Agent: EnflowAI/1.0\r\n"]
    ]);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return "Unknown Location";
    $json = json_decode($result, true);
    if (isset($json['address'])) {
        $addr = $json['address'];
        $parts = [
            $addr['house_number'] ?? '',
            $addr['road'] ?? '',
            $addr['suburb'] ?? '',
            $addr['neighbourhood'] ?? '',
            $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['county'] ?? '',
            $addr['state'] ?? '',
            $addr['country'] ?? '',
        ];
        return implode(', ', array_filter($parts));
    }
    return "Unknown Location";
}

if ($order_number) {

    $stmt = $pdo->prepare("
        SELECT * FROM paid_orders 
        WHERE (plate_order_no = ? OR payment_ref = ?) AND tenant_id = ?
    ");
    $stmt->execute([$order_number, $order_number, $tenant_id]);
    $order = $stmt->fetch();

    if ($order) {
        $response['order']         = $order;
        $response['status']        = $order['order_status'];
        $response['recipient_lat'] = $order['delivery_lat'];
        $response['recipient_lng'] = $order['delivery_lng'];

        $lat = $order['delivery_lat'];
        $lng = $order['delivery_lng'];

        if (!$lat || !$lng) {
            $fullAddress = "Location not provided";
        } else {
            if (!$order['full_address'] || $order['full_address'] == "Unknown Location") {
                $fullAddress = reverseGeocodeOSM($lat, $lng);
                $stmt2 = $pdo->prepare("UPDATE paid_orders SET full_address = ? WHERE id = ? AND tenant_id = ?");
                $stmt2->execute([$fullAddress, $order['id'], $tenant_id]);
            } else {
                $fullAddress = $order['full_address'];
            }
        }

        $response['full_address'] = $fullAddress;

        $stmtItems = $pdo->prepare("SELECT * FROM paid_order_items WHERE paid_order_id = ? AND tenant_id = ?");
        $stmtItems->execute([$order['id'], $tenant_id]);
        $items = $stmtItems->fetchAll();

        $menuStmt = $pdo->prepare("SELECT id, image FROM menu_items WHERE tenant_id = ?");
        $menuStmt->execute([$tenant_id]);
        $menuImages = [];
        foreach ($menuStmt->fetchAll() as $m) {
            $menuImages[$m['id']] = $m['image'];
        }

        foreach ($items as $i) {
            $i['image'] = $menuImages[$i['menu_id']] ?? 'default.png';
            $response['items'][] = $i;
        }

    } else {
        $response['error'] = "Order not found";
    }

} else {
    $response['error'] = "No order number provided";
}

echo json_encode($response);
exit;