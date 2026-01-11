<!-- (This is key for User B) -->
 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->request_id) && !empty($data->owner_id)) {
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // First, check if request exists and is approved
        $check_query = "SELECT br.*, b.title, b.price, b.user_id as actual_owner_id, 
                               u1.name as borrower_name, u1.email as borrower_email,
                               u2.name as owner_name, u2.email as owner_email
                        FROM book_requests br
                        JOIN books b ON br.book_id = b.id
                        JOIN users u1 ON br.requester_id = u1.id
                        JOIN users u2 ON br.owner_id = u2.id
                        WHERE br.id = :request_id AND br.status = 'Approved'";
        
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":request_id", $data->request_id);
        $check_stmt->execute();
        $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            throw new Exception("Approved request not found");
        }
        
        // Verify the owner is the one completing the transaction
        if ($request['actual_owner_id'] != $data->owner_id) {
            throw new Exception("Unauthorized to complete this transaction");
        }
        
        // Check if transaction already exists
        $existing_query = "SELECT id FROM transactions WHERE request_id = :request_id";
        $existing_stmt = $db->prepare($existing_query);
        $existing_stmt->bindParam(":request_id", $data->request_id);
        $existing_stmt->execute();
        
        if ($existing_stmt->rowCount() > 0) {
            throw new Exception("Transaction already exists for this request");
        }
        
        // Get amount (use book price or provided amount)
        $amount = !empty($data->amount) ? floatval($data->amount) : floatval($request['price']);
        
        // Create transaction
        $transaction_query = "INSERT INTO transactions 
                            SET request_id = :request_id, 
                                book_id = :book_id,
                                borrower_id = :borrower_id,
                                lender_id = :lender_id,
                                amount = :amount,
                                payment_method = :payment_method,
                                payment_status = :payment_status,
                                status = :status,
                                completed_at = NOW()";
        
        $transaction_stmt = $db->prepare($transaction_query);
        $transaction_stmt->bindParam(":request_id", $data->request_id);
        $transaction_stmt->bindParam(":book_id", $request['book_id']);
        $transaction_stmt->bindParam(":borrower_id", $request['requester_id']);
        $transaction_stmt->bindParam(":lender_id", $request['owner_id']);
        $transaction_stmt->bindParam(":amount", $amount);
        $transaction_stmt->bindParam(":payment_method", $data->payment_method);
        $transaction_stmt->bindParam(":payment_status", $data->payment_status);
        $transaction_stmt->bindParam(":status", $data->status);
        
        if ($transaction_stmt->execute()) {
            $transaction_id = $db->lastInsertId();
            
            // If payment method provided, create payment record
            if (!empty($data->payment_method) && $amount > 0) {
                $payment_query = "INSERT INTO transaction_payments 
                                 SET transaction_id = :transaction_id,
                                     payment_method = :payment_method,
                                     amount = :amount,
                                     transaction_ref = :transaction_ref,
                                     sender_account = :sender_account,
                                     receiver_account = :receiver_account,
                                     notes = :notes";
                
                $payment_stmt = $db->prepare($payment_query);
                $payment_stmt->bindParam(":transaction_id", $transaction_id);
                $payment_stmt->bindParam(":payment_method", $data->payment_method);
                $payment_stmt->bindParam(":amount", $amount);
                $payment_stmt->bindParam(":transaction_ref", $data->transaction_ref);
                $payment_stmt->bindParam(":sender_account", $data->sender_account);
                $payment_stmt->bindParam(":receiver_account", $data->receiver_account);
                $payment_stmt->bindParam(":notes", $data->payment_notes);
                $payment_stmt->execute();
            }
            
            // Update request status to completed
            $update_request = "UPDATE book_requests SET status = 'Completed' WHERE id = :request_id";
            $update_stmt = $db->prepare($update_request);
            $update_stmt->bindParam(":request_id", $data->request_id);
            $update_stmt->execute();
            
            // Update book status to lent out
            $update_book = "UPDATE books SET status = 'Lent Out' WHERE id = :book_id";
            $book_stmt = $db->prepare($update_book);
            $book_stmt->bindParam(":book_id", $request['book_id']);
            $book_stmt->execute();
            
            // Add transaction history
            $history_query = "INSERT INTO transaction_history 
                            SET transaction_id = :transaction_id, 
                                status = :status,
                                notes = 'Transaction completed successfully'";
            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(":transaction_id", $transaction_id);
            $history_stmt->bindParam(":status", $data->status);
            $history_stmt->execute();
            
            // Send notifications
            $notification = new Notification($db);
            
            // Notify borrower
            $borrower_msg = "📚 Transaction Completed! Your request for '{$request['title']}' has been processed.";
            if ($amount > 0) {
                $borrower_msg .= " Amount: Rs. {$amount}. Please contact the owner for book pickup.";
            }
            
            $notification->user_id = $request['requester_id'];
            $notification->title = "Transaction Completed";
            $notification->message = $borrower_msg;
            $notification->type = "System";
            $notification->related_id = $transaction_id;
            $notification->create();
            
            // Notify lender (owner)
            $owner_msg = "✅ Transaction Completed! You have confirmed the transaction for '{$request['title']}'.";
            if ($amount > 0) {
                $owner_msg .= " Amount: Rs. {$amount} will be processed.";
            }
            
            $notification->user_id = $request['owner_id'];
            $notification->title = "Transaction Confirmed";
            $notification->message = $owner_msg;
            $notification->type = "System";
            $notification->related_id = $transaction_id;
            $notification->create();
            
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                "success" => true,
                "message" => "Transaction completed successfully",
                "transaction_id" => $transaction_id,
                "amount" => $amount,
                "book_title" => $request['title']
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
        "message" => "Required data missing: request_id and owner_id"
    ]);
}
?>