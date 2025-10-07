<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple debug function
function log_debug($message) {
    file_put_contents(__DIR__ . '/login_debug.log', date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

// Start debug logging
log_debug("=== NEW REQUEST ===");
log_debug("Request method: " . $_SERVER['REQUEST_METHOD']);

// CORS headers
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database and user model
include_once '../../config/database.php';
include_once '../../models/User.php';

// Handle GET requests (direct browser access)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $response = [
        "message" => "Login API is working. Use POST method to login.",
        "status" => "active"
    ];
    
    http_response_code(200);
    echo json_encode($response);
    exit();
}

// Handle POST requests (login attempts)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Database connection failed");
        }

        // Get posted data
        $input = file_get_contents("php://input");
        log_debug("Raw input: " . $input);
        
        if (empty($input)) {
            throw new Exception("No input data received");
        }

        $data = json_decode($input);
        
        if (!$data) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg());
        }

        log_debug("JSON parsed: " . print_r($data, true));

        // Validate required fields
        if (empty($data->email) || empty($data->password)) {
            throw new Exception("Email and password are required");
        }

        log_debug("Checking email: " . $data->email);

        // Check if user exists in database
        $user = new User($db);
        $user->email = $data->email;
        
        if ($user->emailExists()) {
            log_debug("Email found in database");
            
            // Verify password
            if (password_verify($data->password, $user->password)) {
                log_debug("Password matches");
                
                $response = [
                    "success" => true,
                    "message" => "Login successful.",
                    "user" => [
                        "user_id" => $user->id,
                        "name" => $user->name,
                        "email" => $user->email,
                        "profile_picture" => $user->profile_picture
                    ]
                ];
                
                http_response_code(200);
                echo json_encode($response);
            } else {
                log_debug("Password does not match");
                throw new Exception("Invalid password");
            }
        } else {
            log_debug("Email not found in database");
            throw new Exception("Email not found");
        }

    } catch (Exception $e) {
        log_debug("ERROR: " . $e->getMessage());
        
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Login failed.",
            "error" => $e->getMessage()
        ]);
    }
}

log_debug("=== REQUEST COMPLETED ===");
?>