<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Message.php';

$database = new Database();
$db = $database->getConnection();

$message = new Message($db);

$user1_id = isset($_GET['user1_id']) ? $_GET['user1_id'] : die();
$user2_id = isset($_GET['user2_id']) ? $_GET['user2_id'] : die();

$result = $message->getMessages($user1_id, $user2_id);

if($result->num_rows > 0){
    $messages_arr = array();
    
    while($row = $result->fetch_assoc()){
        $message_item = array(
            "id" => $row['id'],
            "sender_id" => $row['sender_id'],
            "receiver_id" => $row['receiver_id'],
            "message_text" => $row['message_text'],
            "is_read" => $row['is_read'],
            "created_at" => $row['created_at'],
            "sender_name" => $row['sender_name']
        );
        array_push($messages_arr, $message_item);
    }
    
    http_response_code(200);
    echo json_encode($messages_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No messages found."));
}
?>