<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$filePath = __DIR__ . "/../../GET/JSON/offers.json";
echo file_get_contents($filePath);
?>
