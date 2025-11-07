<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Simple success response for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log the received data
    error_log("Update request received: " . print_r($input, true));
    
    // Simple success response
    echo json_encode([
        'success' => true,
        'message' => 'Request updated successfully',
        'received_data' => $input
    ]);
    exit;
}

// If not POST method
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>