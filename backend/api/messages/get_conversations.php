<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../objects/message.php';

$database = new Database();
$db = $database->getConnection();

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "User ID is required",
        "conversations" => []
    ));
    exit;
}

try {
    // ✅ FIXED: Direct query for conversations
    $query = "SELECT 
                DISTINCT u.id as other_user_id,
                u.name as other_user_name,
                u.email as other_user_email,
                (SELECT message_text FROM messages 
                 WHERE (sender_id = u.id AND receiver_id = :user_id) OR (sender_id = :user_id2 AND receiver_id = u.id)
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages 
                 WHERE (sender_id = u.id AND receiver_id = :user_id3) OR (sender_id = :user_id4 AND receiver_id = u.id)
                 ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE ((sender_id = u.id AND receiver_id = :user_id5) OR (sender_id = :user_id6 AND receiver_id = u.id)) 
                 AND is_read = 0 AND receiver_id = :user_id7) as unread_count
            FROM users u
            WHERE u.id IN (
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = :user_id8 THEN receiver_id 
                        ELSE sender_id 
                    END as other_user
                FROM messages 
                WHERE sender_id = :user_id9 OR receiver_id = :user_id10
            )
            ORDER BY last_message_time DESC";

    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":user_id2", $user_id);
    $stmt->bindParam(":user_id3", $user_id);
    $stmt->bindParam(":user_id4", $user_id);
    $stmt->bindParam(":user_id5", $user_id);
    $stmt->bindParam(":user_id6", $user_id);
    $stmt->bindParam(":user_id7", $user_id);
    $stmt->bindParam(":user_id8", $user_id);
    $stmt->bindParam(":user_id9", $user_id);
    $stmt->bindParam(":user_id10", $user_id);

    $stmt->execute();
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($conversations && count($conversations) > 0) {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "conversations" => $conversations
        ));
    } else {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "No conversations found",
            "conversations" => []
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error retrieving conversations: " . $e->getMessage(),
        "conversations" => []
    ));
}
?>