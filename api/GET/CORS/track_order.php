<?php
// 🔒 Prevent PHP warnings from breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . '/../../SECURE/config.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$order_number = trim($_GET['order_number'] ?? '');

$response = [
    'error' => '',
    'order' => null,
    'items' => [],
    'status' => '',
    'full_address' => '',
    'recipient_lat' => null,
    'recipient_lng' => null
];

// ✅ SAFE reverse geocode (no crash)
function reverseGeocodeOSM($lat, $lng){
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng";

    $context = stream_context_create([
        "http" => [
            "header" => "User-Agent: ArtisanGrillsApp/1.0\r\n"
        ]
    ]);

    $result = @file_get_contents($url, false, $context);

    if($result === false){
        return "Unknown Location";
    }

    $json = json_decode($result, true);

    if(isset($json['address'])){
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

    $stmt = $pdo->prepare("SELECT * FROM paid_orders WHERE plate_order_no = ? OR payment_ref = ?");
    $stmt->execute([$order_number, $order_number]);
    $order = $stmt->fetch();

    if($order){

        $response['order'] = $order;
        $response['status'] = $order['order_status'];

        $lat = $order['delivery_lat'];
        $lng = $order['delivery_lng'];

        $response['recipient_lat'] = $lat;
        $response['recipient_lng'] = $lng;

        // ✅ Safe address logic
        if (!$lat || !$lng){
            $fullAddress = "Location not provided";
        } else {
            if (!$order['full_address'] || $order['full_address'] == "Unknown Location") {
                $fullAddress = reverseGeocodeOSM($lat, $lng);

                $stmt2 = $pdo->prepare("UPDATE paid_orders SET full_address = ? WHERE id = ?");
                $stmt2->execute([$fullAddress, $order['id']]);
            } else {
                $fullAddress = $order['full_address'];
            }
        }

        $response['full_address'] = $fullAddress;

        // ✅ Fetch items
        $stmtItems = $pdo->prepare("SELECT * FROM paid_order_items WHERE paid_order_id = ?");
        $stmtItems->execute([$order['id']]);
        $items = $stmtItems->fetchAll();

        // ✅ Safe menu.json loading
        $menuPath = __DIR__ . "/../JSON/menu.json";
        $menu = file_exists($menuPath)
            ? json_decode(file_get_contents($menuPath), true)
            : [];

        function getImage($menu, $id){
            if(!is_array($menu)) return "default.png";

            foreach($menu as $category){
                if(!is_array($category)) continue;

                foreach($category as $item){
                    if(isset($item['id']) && $item['id'] == $id){
                        return $item['image'] ?? "default.png";
                    }
                }
            }
            return "default.png";
        }

        foreach($items as $i){
            $i['image'] = getImage($menu, $i['menu_id']);
            $response['items'][] = $i;
        }

    } else {
        $response['error'] = "Order not found";
    }

} else {
    $response['error'] = "No order number provided";
}

// ✅ ALWAYS return clean JSON
echo json_encode($response);
exit;
