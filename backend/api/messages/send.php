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
    echo json_encode([
        "success" => false,
        "message" => "Server configuration error: " . $e->getMessage()
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get input data
$input = file_get_contents("php://input");
if (!$input) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No input data received"
    ]);
    exit();
}

$data = json_decode($input);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data received"
    ]);
    exit();
}

// Validate required fields
if (empty($data->sender_id) || empty($data->receiver_id) || empty($data->message_text)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Sender ID, Receiver ID and Message text are required"
    ]);
    exit();
}

try {
    $message = new Message($db);
    
    // Set message properties
    $message->sender_id = $data->sender_id;
    $message->receiver_id = $data->receiver_id;
    $message->message_text = $data->message_text;
    
    // Send message
    if ($message->send()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Message sent successfully",
            "message_id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to send message to database"
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>