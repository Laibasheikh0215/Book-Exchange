<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include files with error handling
try {
    include_once '../../config/database.php';
    include_once '../../models/Message.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server configuration error"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["message" => "User ID is required"]);
    exit();
}

try {
    $message = new Message($db);
    $conversations = [];

    // Get unique conversations using PDO
    $query = "SELECT DISTINCT 
        LEAST(sender_id, receiver_id) as user1_id,
        GREATEST(sender_id, receiver_id) as user2_id,
        (SELECT name FROM users WHERE id = LEAST(sender_id, receiver_id)) as user1_name,
        (SELECT name FROM users WHERE id = GREATEST(sender_id, receiver_id)) as user2_name,
        (SELECT message_text FROM messages m2 
         WHERE ((m2.sender_id = LEAST(m1.sender_id, m1.receiver_id) AND m2.receiver_id = GREATEST(m1.sender_id, m1.receiver_id))
         OR (m2.sender_id = GREATEST(m1.sender_id, m1.receiver_id) AND m2.receiver_id = LEAST(m1.sender_id, m1.receiver_id)))
         ORDER BY m2.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages m2 
         WHERE ((m2.sender_id = LEAST(m1.sender_id, m1.receiver_id) AND m2.receiver_id = GREATEST(m1.sender_id, m1.receiver_id))
         OR (m2.sender_id = GREATEST(m1.sender_id, m1.receiver_id) AND m2.receiver_id = LEAST(m1.sender_id, m1.receiver_id)))
         ORDER BY m2.created_at DESC LIMIT 1) as last_message_at,
        (SELECT COUNT(*) FROM messages m2 
         WHERE m2.sender_id = GREATEST(m1.sender_id, m1.receiver_id) 
         AND m2.receiver_id = LEAST(m1.sender_id, m1.receiver_id)
         AND m2.is_read = 0 AND m2.receiver_id = :user_id) as unread_count
    FROM messages m1
    WHERE m1.sender_id = :user_id OR m1.receiver_id = :user_id
    ORDER BY last_message_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conversations[] = $row;
    }

    echo json_encode($conversations);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server error: " . $e->getMessage()]);
}
?>