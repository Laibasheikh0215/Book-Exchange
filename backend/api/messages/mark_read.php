<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Message.php';

$database = new Database();
$db = $database->getConnection();

$message = new Message($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->user_id) && !empty($data->other_user_id)) {
    if($message->markAsRead($data->user_id, $data->other_user_id)) {
        http_response_code(200);
        echo json_encode(array("message" => "Messages marked as read."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to mark messages as read."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Data is incomplete."));
}
?>