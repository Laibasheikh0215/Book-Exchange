<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id)) {
    $notification->user_id = $data->user_id;
    
    if (!empty($data->notification_id)) {
        // Mark single notification as read
        $notification->id = $data->notification_id;
        if ($notification->markAsRead()) {
            echo json_encode(array("message" => "Notification marked as read.", "success" => true));
        } else {
            echo json_encode(array("message" => "Unable to mark notification as read.", "success" => false));
        }
    } else {
        // Mark all notifications as read
        if ($notification->markAllAsRead()) {
            echo json_encode(array("message" => "All notifications marked as read.", "success" => true));
        } else {
            echo json_encode(array("message" => "Unable to mark notifications as read.", "success" => false));
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "User ID is required.", "success" => false));
}
?>