<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
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

// Include database configuration
include_once '../../config/database.php';
include_once '../../models/Request.php';
include_once '../../models/Book.php';

// Simple debug function
function log_debug($message) {
    file_put_contents(__DIR__ . '/requests_debug.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

log_debug("=== REQUEST CREATE STARTED ===");

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get the raw POST data
    $input = file_get_contents("php://input");
    log_debug("Raw input: " . $input);
    
    if (empty($input)) {
        throw new Exception("No input data received");
    }

    $data = json_decode($input);
    
    if (!$data) {
        throw new Exception("Invalid JSON data: " . json_last_error_msg());
    }

    log_debug("JSON parsed successfully");

    // Check for user_id in the request body instead of session
    if (empty($data->user_id)) {
        log_debug("Error: User ID not provided in request");
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized. User ID required."));
        exit();
    }

    // Validate required fields
    if (empty($data->book_id) || empty($data->request_type)) {
        log_debug("Error: Missing required fields");
        http_response_code(400);
        echo json_encode(array("message" => "Book ID and request type are required."));
        exit();
    }

    // Get book details
    $book = new Book($db);
    $book->id = $data->book_id;
    
    // Create a method to get book details by ID
    if (!$book->getBookById()) {
        log_debug("Error: Book not found with ID: " . $data->book_id);
        http_response_code(404);
        echo json_encode(array("message" => "Book not found."));
        exit();
    }

    // Check if user is trying to request their own book
    if ($book->user_id == $data->user_id) {
        log_debug("Error: User tried to request their own book");
        http_response_code(400);
        echo json_encode(array("message" => "Cannot request your own book."));
        exit();
    }

    // Create request
    $request = new BookRequest($db);
    $request->book_id = $data->book_id;
    $request->requester_id = $data->user_id; // Use user_id from request
    $request->owner_id = $book->user_id;
    $request->request_type = $data->request_type;
    $request->message = $data->message ?? '';
    $request->proposed_return_date = $data->proposed_return_date ?? null;

    log_debug("Creating request: " . print_r([
        'book_id' => $request->book_id,
        'requester_id' => $request->requester_id,
        'owner_id' => $request->owner_id,
        'request_type' => $request->request_type
    ], true));

    if ($request->create()) {
        log_debug("Request created successfully");
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Request sent successfully.",
            "request_id" => $request->id
        ));
    } else {
        log_debug("Error: Failed to create request in database");
        http_response_code(500);
        echo json_encode(array("message" => "Unable to send request."));
    }

} catch (Exception $e) {
    log_debug("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ));
}

log_debug("=== REQUEST CREATE COMPLETED ===");
?>