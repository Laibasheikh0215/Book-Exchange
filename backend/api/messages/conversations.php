<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : die();

try {
    $query = "SELECT c.*, 
                     CASE 
                         WHEN c.user1_id = :user_id THEN c.user2_id 
                         ELSE c.user1_id 
                     END as other_user_id,
                     CASE 
                         WHEN c.user1_id = :user_id THEN u2.name 
                         ELSE u1.name 
                     END as other_user_name,
                     CASE 
                         WHEN c.user1_id = :user_id THEN u2.id 
                         ELSE u1.id 
                     END as other_user_id,
                     (SELECT COUNT(*) FROM messages m 
                      WHERE ((m.sender_id = c.user1_id AND m.receiver_id = c.user2_id) 
                             OR (m.sender_id = c.user2_id AND m.receiver_id = c.user1_id))
                      AND m.receiver_id = :user_id AND m.is_read = 0) as unread_count
              FROM conversations c
              JOIN users u1 ON c.user1_id = u1.id
              JOIN users u2 ON c.user2_id = u2.id
              WHERE c.user1_id = :user_id OR c.user2_id = :user_id
              ORDER BY c.last_message_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    
    $conversations = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conversation_item = array(
            "id" => $row['id'],
            "user1_id" => $row['user1_id'],
            "user2_id" => $row['user2_id'],
            "other_user_id" => $row['other_user_id'],
            "other_user_name" => $row['other_user_name'] ? $row['other_user_name'] : 'Unknown User',
            "last_message" => $row['last_message'],
            "last_message_at" => $row['last_message_at'],
            "unread_count" => $row['unread_count']
        );
        array_push($conversations, $conversation_item);
    }
    
    http_response_code(200);
    echo json_encode($conversations);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving conversations: " . $e->getMessage()));
}
?>