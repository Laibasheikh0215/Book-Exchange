<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../../config/database.php';
include_once '../../objects/message.php';

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->user_id) && !empty($data->other_user_id)) {
    if($message->markAsRead($data->user_id, $data->other_user_id)) {
        echo json_encode(array("success" => true, "message" => "Messages marked as read"));
    } else {
        echo json_encode(array("success" => false, "message" => "Unable to mark messages as read"));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Incomplete data"));
}
?>