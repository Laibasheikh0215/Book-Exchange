<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (!$data) {
        throw new Exception("Invalid JSON data");
    }

    // Validate input
    if (empty($data->username) || empty($data->password)) {
        throw new Exception("Username and password are required");
    }

    // Check if user exists
    $query = "SELECT id, username, email, password, role, is_active 
              FROM admin_users 
              WHERE username = ? AND is_active = 1 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$data->username]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Admin user not found or inactive");
    }

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // SIMPLE PASSWORD CHECK - Plain text compare
    if ($data->password === $admin['password']) {
        // Update last login
        $updateQuery = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$admin['id']]);

        // Success response
        echo json_encode([
            "success" => true,
            "message" => "Login successful!",
            "admin" => [
                "id" => $admin['id'],
                "username" => $admin['username'],
                "email" => $admin['email'],
                "role" => $admin['role']
            ]
        ]);
    } else {
        // Debug information
        error_log("Password mismatch - Entered: " . $data->password . ", Stored: " . $admin['password']);
        throw new Exception("Password incorrect. Stored password: " . $admin['password']);
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>