<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Message.php';

$database = new Database();
$db = $database->getConnection();

$message = new Message($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$stmt = $message->getConversations($user_id);
$num = $stmt->rowCount();

if($num > 0){
    $conversations_arr = array();
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $conversation_item = array(
            "other_user_id" => $row['other_user_id'],
            "other_user_name" => $row['other_user_name'],
            "other_user_avatar" => $row['other_user_avatar'],
            "last_message" => $row['last_message'],
            "last_message_time" => $row['last_message_at'],
            "unread_count" => $row['unread_count']
        );
        array_push($conversations_arr, $conversation_item);
    }
    
    http_response_code(200);
    echo json_encode($conversations_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No conversations found."));
}
?>