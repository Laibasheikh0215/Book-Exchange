<?php
// Enable error logging but don't display errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/update_errors.log');
error_reporting(E_ALL);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log function for debugging
function log_message($message) {
    file_put_contents(__DIR__ . '/update_debug.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

log_message("Update API called: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

try {
    // Get the raw POST data
    $input = file_get_contents("php://input");
    log_message("Raw input: " . $input);
    
    if (empty($input)) {
        throw new Exception("No input data received");
    }

    $data = json_decode($input);
    
    if (!$data) {
        throw new Exception("Invalid JSON data: " . json_last_error_msg());
    }

    // Validate required fields
    if (empty($data->request_id) || empty($data->status)) {
        throw new Exception("Request ID and status are required");
    }
    
    if (empty($data->user_id)) {
        throw new Exception("User ID is required");
    }
    
    // Use absolute paths for includes
    $database_path = __DIR__ . '/../../config/database.php';
    $model_path = __DIR__ . '/../../models/Request.php';
    
    log_message("Database path: " . $database_path);
    log_message("Model path: " . $model_path);
    
    if (!file_exists($database_path)) {
        throw new Exception("Database configuration file not found at: " . $database_path);
    }
    
    if (!file_exists($model_path)) {
        throw new Exception("Request model file not found at: " . $model_path);
    }
    
    require_once $database_path;
    require_once $model_path;
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Update request status
    $request = new BookRequest($db);
    $request->id = $data->request_id;
    $request->owner_id = $data->user_id; // The owner is the one who can approve/reject
    $request->status = $data->status;
    
    log_message("Attempting to update request ID: " . $data->request_id . " to status: " . $data->status . " by user ID: " . $data->user_id);
    
    if ($request->updateStatus()) {
        log_message("Request updated successfully");
        
        $response = [
            'success' => true,
            'message' => "Request " . strtolower($data->status) . " successfully."
        ];
        
        echo json_encode($response);
    } else {
        throw new Exception("Unable to update request. It may not exist or you may not have permission.");
    }
    
} catch (Exception $e) {
    log_message("Error: " . $e->getMessage());
    
    // Return JSON error response instead of HTML
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'UPDATE_ERROR'
    ]);
}
?>