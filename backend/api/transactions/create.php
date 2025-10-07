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

    // Check if book_requests table exists
    $check_table = $db->prepare("SHOW TABLES LIKE 'book_requests'");
    $check_table->execute();
    
    if ($check_table->rowCount() === 0) {
        throw new Exception("Transactions system is not setup. Please contact administrator.");
    }

    $input = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    $required = ['book_id', 'requester_id', 'owner_id', 'request_type', 'message'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // ... rest of your existing create.php code ...

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>