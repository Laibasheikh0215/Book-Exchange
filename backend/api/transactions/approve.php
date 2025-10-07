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

    $input = json_decode(file_get_contents("php://input"), true);
    $request_id = $input['request_id'] ?? null;
    $user_id = $input['user_id'] ?? null;

    if (!$request_id || !$user_id) {
        throw new Exception("Request ID and User ID required");
    }

    // Verify user owns the book
    $check_ownership = $db->prepare("SELECT br.id FROM book_requests br WHERE br.id = ? AND br.owner_id = ?");
    $check_ownership->execute([$request_id, $user_id]);
    
    if ($check_ownership->rowCount() === 0) {
        throw new Exception("You are not authorized to approve this request");
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Update request status
        $update_request = $db->prepare("UPDATE book_requests SET status = 'Approved', updated_at = NOW() WHERE id = ?");
        $update_request->execute([$request_id]);

        // Update book status to Lent Out
        $get_book_id = $db->prepare("SELECT book_id FROM book_requests WHERE id = ?");
        $get_book_id->execute([$request_id]);
        $book_id = $get_book_id->fetch(PDO::FETCH_ASSOC)['book_id'];

        $update_book = $db->prepare("UPDATE books SET status = 'Lent Out' WHERE id = ?");
        $update_book->execute([$book_id]);

        // If swap, update the swap book status too
        $get_swap_book = $db->prepare("SELECT swap_book_id FROM book_requests WHERE id = ? AND swap_book_id IS NOT NULL");
        $get_swap_book->execute([$request_id]);
        $swap_book = $get_swap_book->fetch(PDO::FETCH_ASSOC);
        
        if ($swap_book) {
            $update_swap_book = $db->prepare("UPDATE books SET status = 'Lent Out' WHERE id = ?");
            $update_swap_book->execute([$swap_book['swap_book_id']]);
        }

        // Log the approval
        $log_query = "INSERT INTO transaction_logs (transaction_id, action, description, performed_by, user_type) 
                      VALUES (?, 'request_approved', 'Book request approved by owner', ?, 'user')";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->execute([$request_id, $user_id]);

        $db->commit();

        echo json_encode([
            "success" => true,
            "message" => "Request approved successfully"
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>