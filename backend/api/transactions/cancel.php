<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents("php://input"), true);
    $request_id = $input['request_id'] ?? null;
    $user_id = $input['user_id'] ?? null;

    if (!$request_id || !$user_id) {
        throw new Exception("Request ID and User ID required");
    }

    // Verify user is the requester
    $check_requester = $db->prepare("SELECT br.id FROM book_requests br WHERE br.id = ? AND br.requester_id = ?");
    $check_requester->execute([$request_id, $user_id]);
    
    if ($check_requester->rowCount() === 0) {
        throw new Exception("You are not authorized to cancel this request");
    }

    // Update request status
    $update_request = $db->prepare("UPDATE book_requests SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
    $update_request->execute([$request_id]);

    // Log the cancellation
    $log_query = "INSERT INTO transaction_logs (transaction_id, action, description, performed_by, user_type) 
                  VALUES (?, 'request_cancelled', 'Book request cancelled by requester', ?, 'user')";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->execute([$request_id, $user_id]);

    echo json_encode([
        "success" => true,
        "message" => "Request cancelled successfully"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>