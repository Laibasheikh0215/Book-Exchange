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
    echo json_encode(["count" => 0, "message" => "Server configuration error"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["count" => 0, "message" => "User ID is required"]);
    exit();
}

try {
    $message = new Message($db);
    $count = $message->countUnreadMessages($user_id);
    
    echo json_encode(["count" => $count]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["count" => 0, "message" => "Server error"]);
}
?>