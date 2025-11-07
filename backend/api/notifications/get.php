<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(array("message" => "User ID is required.", "success" => false));
    exit();
}

$user_id = $_GET['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

try {
    $stmt = $notification->getUserNotifications($user_id, $limit);
    $num = $stmt->rowCount();

    if ($num > 0) {
        $notifications_arr = array();
        $notifications_arr["notifications"] = array();
        $notifications_arr["count"] = $num;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notification_item = array(
                "id" => $row['id'],
                "user_id" => $row['user_id'],
                "title" => $row['title'],
                "message" => $row['message'],
                "type" => $row['type'],
                "is_read" => (bool)$row['is_read'],
                "related_id" => $row['related_id'],
                "created_at" => $row['created_at']
            );
            array_push($notifications_arr["notifications"], $notification_item);
        }
        
        http_response_code(200);
        echo json_encode($notifications_arr);
    } else {
        http_response_code(200);
        echo json_encode(array(
            "notifications" => array(),
            "count" => 0,
            "message" => "No notifications found."
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "message" => "Error retrieving notifications: " . $e->getMessage(),
        "success" => false
    ));
}
?>