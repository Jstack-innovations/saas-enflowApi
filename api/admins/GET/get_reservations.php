<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

session_start();
// Uncomment for auth if needed
// if (!isset($_SESSION['admin_id'])) {
//     http_response_code(401);
//     echo json_encode(["error" => "Unauthorized"]);
//     exit;
//}

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

/* Load tables JSON */
$tablesFile = __DIR__ . "/../../GET/JSON/tables.json";

$tablesJson = [];

if (file_exists($tablesFile)) {
    $tablesJson = json_decode(file_get_contents($tablesFile), true) ?? [];
}

/* Flatten tables from floors */
if (isset($tablesJson['floors']) && is_array($tablesJson['floors'])) {
    foreach ($tablesJson['floors'] as $floorTables) {
        foreach ($floorTables as $t) {
            $tables[$t['id']] = $t;
        }
    }
}

header('Content-Type: application/json');

/* Fetch reservations */
$res = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC");
$reservations = [];
while ($row = $res->fetch_assoc()) {
    $reservations[] = $row;
}

echo json_encode([
    "reservations" => $reservations,
    "tables" => array_values($tables)
]);
exit;
?>
