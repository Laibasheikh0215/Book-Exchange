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

if (empty($data->user_id) || empty($data->title) || empty($data->message)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID, title and message are required"]);
    exit();
}

try {
    $notification = new Notification($db);
    
    $notification->user_id = $data->user_id;
    $notification->title = $data->title;
    $notification->message = $data->message;
    $notification->type = $data->type ?? 'System';
    $notification->related_id = $data->related_id ?? null;
    
    if ($notification->create()) {
        echo json_encode(["success" => true, "message" => "Notification created"]);
    } else {
        throw new Exception("Failed to create notification");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>