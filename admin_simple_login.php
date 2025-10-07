<?php
// Simple admin login with absolute paths
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers FIRST
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get the raw POST data
    $input = file_get_contents("php://input");
    $data = json_decode($input);
    
    // Check if we got valid JSON
    if ($data === null) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON data"
        ]);
        exit();
    }
    
    // Check if username and password are provided
    if (empty($data->username) || empty($data->password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Username and password are required"
        ]);
        exit();
    }
    
    // HARDCODED CREDENTIALS FOR TESTING
    $valid_username = 'admin';
    $valid_password = 'admin123';
    
    // Check credentials
    if ($data->username === $valid_username && $data->password === $valid_password) {
        // SUCCESSFUL LOGIN
        $response = [
            "success" => true,
            "message" => "Login successful! Redirecting to dashboard...",
            "admin" => [
                "admin_id" => 1,
                "username" => "admin",
                "email" => "admin@bookexchange.com",
                "role" => "super_admin"
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response);
        
    } else {
        // FAILED LOGIN
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password"
        ]);
    }
    
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST."
    ]);
}
?>