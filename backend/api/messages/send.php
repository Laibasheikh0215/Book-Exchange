<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../objects/message.php';

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->sender_id) && !empty($data->receiver_id) && !empty($data->message_text)) {
    
    $message->sender_id = $data->sender_id;
    $message->receiver_id = $data->receiver_id;
    $message->message_text = $data->message_text;
    $message->book_id = $data->book_id ?? null;
    $message->is_read = 0;
    $message->created_at = date('Y-m-d H:i:s');

    if($message->create()) {
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Message sent successfully",
            "message_id" => $db->lastInsertId()
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("success" => false, "message" => "Unable to send message"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data"));
}
?>