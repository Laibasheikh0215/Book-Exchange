<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
    echo json_encode(["success" => false, "message" => "Server configuration error"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$input = file_get_contents("php://input");
$data = json_decode($input);

if (empty($data->sender_id) || empty($data->receiver_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Sender ID and Receiver ID are required"]);
    exit();
}

try {
    $message = new Message($db);
    $success = $message->markAsRead($data->sender_id, $data->receiver_id);
    
    echo json_encode(["success" => $success, "message" => "Messages marked as read"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
}
?>