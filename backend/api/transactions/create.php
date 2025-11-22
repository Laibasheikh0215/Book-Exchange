<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->request_id) && !empty($data->borrower_id) && !empty($data->book_id)) {
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Get request details
        $request_query = "SELECT br.*, b.price, b.user_id as owner_id, b.title as book_title 
                         FROM book_requests br 
                         JOIN books b ON br.book_id = b.id 
                         WHERE br.id = :request_id";
        $request_stmt = $db->prepare($request_query);
        $request_stmt->bindParam(":request_id", $data->request_id);
        $request_stmt->execute();
        $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            throw new Exception("Request not found");
        }
        
        // Use book price if amount not provided
        $amount = !empty($data->amount) ? $data->amount : $request['price'];
        
        // Create transaction
        $transaction_query = "INSERT INTO transactions 
                             SET request_id = :request_id, book_id = :book_id, 
                                 borrower_id = :borrower_id, lender_id = :lender_id,
                                 amount = :amount, payment_method = :payment_method,
                                 status = :status, completed_at = NOW()";
        
        $transaction_stmt = $db->prepare($transaction_query);
        $transaction_stmt->bindParam(":request_id", $data->request_id);
        $transaction_stmt->bindParam(":book_id", $data->book_id);
        $transaction_stmt->bindParam(":borrower_id", $data->borrower_id);
        $transaction_stmt->bindParam(":lender_id", $request['owner_id']);
        $transaction_stmt->bindParam(":amount", $amount);
        $transaction_stmt->bindParam(":payment_method", $data->payment_method);
        $transaction_stmt->bindParam(":status", $data->status);
        
        if ($transaction_stmt->execute()) {
            $transaction_id = $db->lastInsertId();
            
            // Add to transaction history
            $history_query = "INSERT INTO transaction_history 
                             SET transaction_id = :transaction_id, 
                                 status = :status, 
                                 notes = 'Transaction created successfully'";
            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(":transaction_id", $transaction_id);
            $history_stmt->bindParam(":status", $data->status);
            $history_stmt->execute();
            
            // Update request status to completed
            $update_request = "UPDATE book_requests SET status = 'Completed' WHERE id = :request_id";
            $update_stmt = $db->prepare($update_request);
            $update_stmt->bindParam(":request_id", $data->request_id);
            $update_stmt->execute();
            
            // Update book status to lent out
            $update_book = "UPDATE books SET status = 'Lent Out' WHERE id = :book_id";
            $book_stmt = $db->prepare($update_book);
            $book_stmt->bindParam(":book_id", $data->book_id);
            $book_stmt->execute();
            
            // Commit transaction
            $db->commit();
            
            // Send notification to lender
            $notification_msg = "Payment received for book '{$request['book_title']}'. Amount: Rs. " . $amount;
            $notification_query = "INSERT INTO notifications 
                                  SET user_id = :user_id, title = 'Payment Received', 
                                      message = :message, type = 'System'";
            $notification_stmt = $db->prepare($notification_query);
            $notification_stmt->bindParam(":user_id", $request['owner_id']);
            $notification_stmt->bindParam(":message", $notification_msg);
            $notification_stmt->execute();
            
            echo json_encode([
                "success" => true,
                "message" => "Transaction completed successfully",
                "transaction_id" => $transaction_id,
                "amount" => $amount
            ]);
            
        } else {
            throw new Exception("Failed to create transaction");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
    
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Required data missing: request_id, borrower_id, book_id"
    ]);
}
?>