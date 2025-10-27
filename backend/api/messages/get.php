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

$user1_id = isset($_GET['user1_id']) ? $_GET['user1_id'] : null;
$user2_id = isset($_GET['user2_id']) ? $_GET['user2_id'] : null;

if (!$user1_id || !$user2_id) {
    http_response_code(400);
    echo json_encode(["message" => "Both user IDs are required"]);
    exit();
}

try {
    $message = new Message($db);
    $stmt = $message->getMessages($user1_id, $user2_id);
    $messages = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = $row;
    }

    echo json_encode($messages);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server error: " . $e->getMessage()]);
}
?>