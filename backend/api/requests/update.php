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

include_once '../../config/database.php';
include_once '../../models/Request.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (!$data) {
        throw new Exception("Invalid JSON data");
    }

    // Validate required fields
    if (empty($data->request_id) || empty($data->status)) {
        throw new Exception("Request ID and status are required");
    }
    
    if (empty($data->user_id)) {
        throw new Exception("User ID is required");
    }

    // Update request status
    $request = new Request($db);
    $request->id = $data->request_id;
    $request->owner_id = $data->user_id;
    $request->status = $data->status;
    
    if ($request->updateStatus()) {
        $response = [
            'success' => true,
            'message' => "Request " . strtolower($data->status) . " successfully."
        ];
        
        echo json_encode($response);
    } else {
        throw new Exception("Unable to update request. It may not exist or you may not have permission.");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'UPDATE_ERROR'
    ]);
}
?>