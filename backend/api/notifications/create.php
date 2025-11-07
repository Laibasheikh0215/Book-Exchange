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

if (!empty($data->user_id) && !empty($data->title) && !empty($data->message)) {
    $notification->user_id = $data->user_id;
    $notification->title = $data->title;
    $notification->message = $data->message;
    $notification->type = $data->type ?? 'System';
    $notification->related_id = $data->related_id ?? null;

    if ($notification->create()) {
        http_response_code(201);
        echo json_encode(array("message" => "Notification created successfully.", "success" => true));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to create notification.", "success" => false));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to create notification. Data is incomplete.", "success" => false));
}
?>