<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

$input = file_get_contents("php://input");
$data = json_decode($input);

if (empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit();
}

try {
    $notification = new Notification($db);
    
    if (!empty($data->notification_id)) {
        // Mark single notification as read
        $success = $notification->markAsRead($data->notification_id, $data->user_id);
    } else {
        // Mark all as read
        $success = $notification->markAllAsRead($data->user_id);
    }
    
    echo json_encode(["success" => $success, "message" => "Notifications marked as read"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
}
?>