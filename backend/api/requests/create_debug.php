<?php
// Temporary debug version
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple response for testing
$input = file_get_contents("php://input");
$data = json_decode($input);

error_log("Received data: " . print_r($data, true));

if ($data && !empty($data->book_id) && !empty($data->user_id)) {
    http_response_code(201);
    echo json_encode(array(
        "success" => true,
        "message" => "Request sent successfully (DEBUG MODE).",
        "request_id" => 999,
        "debug_data" => $data
    ));
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Missing required fields in DEBUG MODE",
        "received_data" => $data
    ));
}
?>