<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS headers
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database and admin model
include_once '../../config/database.php';
include_once '../../models/Admin.php';

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
        $data = json_decode($input);
        
        if (!$data) {
            throw new Exception("Invalid JSON data");
        }

        // Validate required fields
        if (empty($data->username) || empty($data->password)) {
            throw new Exception("Username and password are required");
        }

        // Check admin credentials
        $admin = new Admin($db);
        $admin->username = $data->username;
        $admin->password = $data->password;
        
        if ($admin->login()) {
            // Log the login action
            $admin->logAction('login', 'Admin logged in successfully');
            
            $response = [
                "success" => true,
                "message" => "Login successful.",
                "admin" => [
                    "admin_id" => $admin->id,
                    "username" => $admin->username,
                    "email" => $admin->email,
                    "role" => $admin->role
                ]
            ];
            
            http_response_code(200);
            echo json_encode($response);
        } else {
            throw new Exception("Invalid username or password");
        }

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Login failed.",
            "error" => $e->getMessage()
        ]);
    }
}
?>