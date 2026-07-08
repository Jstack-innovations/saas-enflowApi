<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;
require_once __DIR__ . '/../../SECURE/tenant.php';

$tenant_id = getTenantId($conn);

$data = json_decode(file_get_contents("php://input"), true);

$name           = $data['name']           ?? '';
$phone          = $data['phone']          ?? '';
$table_no       = $data['table_no']       ?? '';
$order_type     = $data['order_type']     ?? '';
$total_amount   = $data['total_amount']   ?? '';
$payment_ref    = $data['payment_ref']    ?? '';
$order_status   = $data['order_status']   ?? 'Order placed';
$full_address   = $data['full_address']   ?? '';
$plate_order_no = $data['plate_order_no'] ?? '';

$valid_status = ['Order placed','Cooking','Cooking done','Out for delivery','Delivered','Served','Picked up'];
if (!in_array($order_status, $valid_status)) {
    $order_status = 'Order placed';
}

$stmt = $conn->prepare("
    INSERT INTO paid_orders 
    (tenant_id, name, phone, table_no, order_type, total_amount, payment_ref, order_status, full_address, plate_order_no)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("isssssssss", $tenant_id, $name, $phone, $table_no, $order_type, $total_amount, $payment_ref, $order_status, $full_address, $plate_order_no);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}