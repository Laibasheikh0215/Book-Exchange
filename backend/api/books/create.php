<?php
// Enable full error reporting temporarily
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set absolute path for log file
$log_file = __DIR__ . '/add_book_debug.log';

// Enhanced debug function
function log_debug($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "{$timestamp} - {$message}" . PHP_EOL, FILE_APPEND);
}

// Start with detailed information
log_debug("=== ADD BOOK REQUEST STARTED ===");

// CORS headers
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    log_debug("Handling OPTIONS preflight request");
    http_response_code(200);
    exit();
}

try {
    log_debug("Including database files...");
    
    // CORRECT PATHS - Use absolute paths with correct structure
    $database_path = __DIR__ . '/../../config/database.php'; // Fixed path
    $book_model_path = __DIR__ . '/../../models/Book.php';   // Fixed path
    
    log_debug("Database path: " . $database_path);
    log_debug("Book model path: " . $book_model_path);
    
    if (!file_exists($database_path)) {
        throw new Exception("Database config file not found: " . $database_path);
    }
    
    if (!file_exists($book_model_path)) {
        throw new Exception("Book model file not found: " . $book_model_path);
    }
    
    include_once $database_path;
    include_once $book_model_path;
    
    log_debug("Files included successfully");

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    log_debug("Database connection successful");

    // Get posted data
    $input = file_get_contents("php://input");
    log_debug("Raw input length: " . strlen($input) . " characters");
    
    if (empty($input)) {
        throw new Exception("No input data received");
    }

    $data = json_decode($input);
    
    if (!$data) {
        $json_error = json_last_error_msg();
        throw new Exception("Invalid JSON data: " . $json_error);
    }

    log_debug("JSON parsed successfully");

    // Validate required fields
    if (empty($data->user_id)) {
        throw new Exception("User ID is required");
    }
    
    if (empty($data->title)) {
        throw new Exception("Title is required");
    }
    
    if (empty($data->author)) {
        throw new Exception("Author is required");
    }

    log_debug("Required fields validation passed");

    // Prepare book object
    $book = new Book($db);
    
    $book->user_id = $data->user_id;
    $book->title = $data->title;
    $book->author = $data->author;
    $book->isbn = $data->isbn ?? '';
    $book->genre = $data->genre ?? '';
    $book->condition = $data->condition ?? 'Good';
    $book->description = $data->description ?? '';

    log_debug("Book object prepared: " . $book->title . " by " . $book->author);

    // Create the book
    if ($book->create()) {
        $response = [
            "success" => true,
            "message" => "Book was added successfully.",
            "book_id" => $book->id
        ];
        
        log_debug("Book created successfully with ID: " . $book->id);
        
        http_response_code(201);
        echo json_encode($response);
    } else {
        throw new Exception("Unable to add book. Database operation failed.");
    }

} catch (Exception $e) {
    log_debug("ERROR: " . $e->getMessage());
    
    $error_details = [
        "success" => false,
        "message" => "Failed to add book.",
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ];
    
    http_response_code(400);
    echo json_encode($error_details);
}

log_debug("=== ADD BOOK REQUEST COMPLETED ===");
?>