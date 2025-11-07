<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(array("message" => "User ID is required.", "success" => false));
    exit();
}

$user_id = $_GET['user_id'];

try {
    $count = $notification->getUnreadCount($user_id);
    echo json_encode(array("count" => $count, "success" => true));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "message" => "Error getting unread count: " . $e->getMessage(),
        "success" => false,
        "count" => 0
    ));
}
?>