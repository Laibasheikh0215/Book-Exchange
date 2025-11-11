<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../objects/message.php';

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

// Get parameters
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$other_user_id = isset($_GET['other_user_id']) ? intval($_GET['other_user_id']) : null;

if (!$user_id || !$other_user_id) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "User ID and Other User ID are required",
        "messages" => []
    ));
    exit;
}

try {
    // ✅ FIXED: Direct query instead of using the class method
    $query = "SELECT m.*, 
                u1.name as sender_name,
                u2.name as receiver_name
            FROM messages m
            LEFT JOIN users u1 ON m.sender_id = u1.id
            LEFT JOIN users u2 ON m.receiver_id = u2.id
            WHERE (m.sender_id = :user_id AND m.receiver_id = :other_user_id) 
               OR (m.sender_id = :other_user_id2 AND m.receiver_id = :user_id2)
            ORDER BY m.created_at ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":other_user_id", $other_user_id);
    $stmt->bindParam(":other_user_id2", $other_user_id);
    $stmt->bindParam(":user_id2", $user_id);
    
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($messages && count($messages) > 0) {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "messages" => $messages
        ));
    } else {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "No messages found",
            "messages" => []
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error retrieving messages: " . $e->getMessage(),
        "messages" => []
    ));
}
?>