<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
include_once '../../db/Database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is valid
if (!isset($data->request_id) || !isset($data->status) || !isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input data"]);
    exit;
}

try {
    // First verify the request exists and user has permission
    $verify_query = "SELECT owner_id FROM book_requests WHERE id = :request_id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(':request_id', $data->request_id);
    $verify_stmt->execute();
    
    $request_data = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request_data) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Request not found"]);
        exit;
    }
    
    // Update the request status
    $query = "UPDATE book_requests SET status = :status WHERE id = :request_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':request_id', $data->request_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Request updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update request"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>