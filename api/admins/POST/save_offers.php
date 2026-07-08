<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/tenant.php";

$tenant_id = getTenantId($conn);

$data = json_decode(file_get_contents("php://input"), true);

// $data is expected to be an array of offers
if (!is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

// Delete existing offers for this tenant and replace
$conn->begin_transaction();

try {
    $del = $conn->prepare("DELETE FROM offers WHERE tenant_id = ?");
    $del->bind_param("i", $tenant_id);
    $del->execute();

    $stmt = $conn->prepare("
        INSERT INTO offers (tenant_id, title, main_text, sub_text, bg_color, image)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($data as $offer) {
        $title     = $offer['title']     ?? '';
        $main_text = $offer['main_text'] ?? '';
        $sub_text  = $offer['sub_text']  ?? '';
        $bg_color  = $offer['bg_color']  ?? '';
        $image     = $offer['image']     ?? '';

        $stmt->bind_param("isssss", $tenant_id, $title, $main_text, $sub_text, $bg_color, $image);
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}