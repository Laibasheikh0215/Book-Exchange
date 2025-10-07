<?php
// Enable error logging but don't display errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/my_requests_errors.log');
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple debug function
function log_debug($message) {
    file_put_contents(__DIR__ . '/my_requests_debug.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

log_debug("=== MY_REQUESTS API CALLED ===");

try {
    // Include database configuration
    include_once '../../config/database.php';
    include_once '../../models/Request.php';

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get user ID from query parameter
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    log_debug("User ID: " . $user_id);

    if (!$user_id) {
        throw new Exception("User ID is required");
    }

    $request = new BookRequest($db);
    
    // Get both incoming and outgoing requests
    log_debug("Fetching incoming requests...");
    $incoming_stmt = $request->getByOwner($user_id);
    $incoming_requests = $incoming_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    log_debug("Fetching outgoing requests...");
    $outgoing_stmt = $request->getByRequester($user_id);
    $outgoing_requests = $outgoing_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    log_debug("Incoming: " . count($incoming_requests) . ", Outgoing: " . count($outgoing_requests));

    echo json_encode(array(
        "success" => true,
        "incoming_requests" => $incoming_requests,
        "outgoing_requests" => $outgoing_requests
    ));

} catch (Exception $e) {
    log_debug("ERROR: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ));
}
?>