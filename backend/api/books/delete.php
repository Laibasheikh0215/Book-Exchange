<?php
// delete.php - DEBUG VERSION

// TEMPORARY: Enable ALL errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");

// Log start
error_log("=== DELETE.PH P STARTED ===");

try {
    // Log the raw input
    $input = file_get_contents("php://input");
    error_log("Raw input received: " . $input);
    
    if (empty($input)) {
        throw new Exception("No data received in request body");
    }
    
    // Decode JSON
    $data = json_decode($input);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }
    
    error_log("Decoded data: " . print_r($data, true));
    
    // Validate
    if (empty($data->id)) {
        throw new Exception("Book ID is required");
    }
    
    if (empty($data->user_id)) {
        throw new Exception("User ID is required");
    }
    
    $book_id = $data->id;
    $user_id = $data->user_id;
    
    error_log("Attempting to delete book ID: $book_id for user: $user_id");
    
    // 1. Check if include files exist
    $db_config = __DIR__ . '/../../config/database.php';
    $book_model = __DIR__ . '/../../models/Book.php';
    
    error_log("Database config path: $db_config");
    error_log("Book model path: $book_model");
    
    if (!file_exists($db_config)) {
        throw new Exception("Database config file not found: $db_config");
    }
    
    if (!file_exists($book_model)) {
        throw new Exception("Book model file not found: $book_model");
    }
    
    // 2. Include files
    include_once $db_config;
    include_once $book_model;
    
    error_log("Files included successfully");
    
    // 3. Create database connection
    $database = new Database();
    error_log("Database class instantiated");
    
    $db = $database->getConnection();
    error_log("Database connection attempt made");
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    error_log("Database connected successfully");
    
    // 4. Create Book object
    $book = new Book($db);
    error_log("Book object created");
    
    // 5. Set properties
    $book->id = $book_id;
    $book->user_id = $user_id;
    
    error_log("Properties set: id=$book_id, user_id=$user_id");
    
    // 6. Attempt delete
    error_log("Calling delete() method...");
    $result = $book->delete();
    error_log("Delete method returned: " . ($result ? 'true' : 'false'));
    
    if ($result) {
        $response = [
            "success" => true,
            "message" => "Book deleted successfully"
        ];
        error_log("Delete successful");
    } else {
        $response = [
            "success" => false,
            "message" => "Failed to delete book. Book not found or you don't have permission."
        ];
        error_log("Delete failed - book not found or no permission");
    }
    
    // 7. Send response
    echo json_encode($response);
    error_log("Response sent: " . json_encode($response));
    
} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    $response = [
        "success" => false,
        "message" => "Server Error: " . $e->getMessage(),
        "error_details" => [
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString()
        ]
    ];
    
    http_response_code(500);
    echo json_encode($response);
    
    error_log("Error response sent");
}

error_log("=== DELETE.PHP ENDED ===");
?>