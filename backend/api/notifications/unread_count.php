<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["count" => 0, "message" => "User ID is required"]);
    exit();
}

try {
    $notification = new Notification($db);
    $count = $notification->countUnread($user_id);
    
    echo json_encode(["count" => $count]);

} catch (Exception $e) {
    echo json_encode(["count" => 0]);
}
?>