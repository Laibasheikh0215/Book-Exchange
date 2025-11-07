<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->sender_id) && !empty($data->receiver_id) && !empty($data->message_text)) {
    
    try {
        // Insert message into database
        $query = "INSERT INTO messages SET sender_id=:sender_id, receiver_id=:receiver_id, message_text=:message_text";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":sender_id", $data->sender_id);
        $stmt->bindParam(":receiver_id", $data->receiver_id);
        $stmt->bindParam(":message_text", $data->message_text);
        
        if ($stmt->execute()) {
            // Update conversations table
            $conversation_query = "INSERT INTO conversations (user1_id, user2_id, last_message, last_message_at) 
                                   VALUES (:user1_id, :user2_id, :last_message, NOW())
                                   ON DUPLICATE KEY UPDATE 
                                   last_message = :last_message, 
                                   last_message_at = NOW()";
            
            $conversation_stmt = $db->prepare($conversation_query);
            
            // Always store smaller user_id first for consistency
            $user1_id = min($data->sender_id, $data->receiver_id);
            $user2_id = max($data->sender_id, $data->receiver_id);
            $last_message = substr($data->message_text, 0, 100); // First 100 characters
            
            $conversation_stmt->bindParam(":user1_id", $user1_id);
            $conversation_stmt->bindParam(":user2_id", $user2_id);
            $conversation_stmt->bindParam(":last_message", $last_message);
            
            $conversation_stmt->execute();
            
            http_response_code(201);
            echo json_encode(array(
                "success" => true,
                "message" => "Message sent successfully.",
                "message_id" => $db->lastInsertId()
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("success" => false, "message" => "Unable to send message."));
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
    }
    
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Unable to send message. Data is incomplete."));
}
?>