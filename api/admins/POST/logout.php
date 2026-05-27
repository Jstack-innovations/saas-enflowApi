<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

// Delete token from DB using admin_id set by authGuard
$stmt = $conn->prepare("DELETE FROM admin_sessions WHERE admin_id = ?");
$stmt->bind_param("i", $GLOBALS['admin_id']);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Logged out"]);
