<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Get all transactions with detailed information
        $query = "SELECT 
                    br.id,
                    br.book_id,
                    b.title as book_title,
                    b.author as book_author,
                    req.name as requester_name,
                    req.email as requester_email,
                    own.name as owner_name,
                    own.email as owner_email,
                    br.request_type,
                    br.status,
                    br.message,
                    br.proposed_return_date,
                    br.swap_book_id,
                    sb.title as swap_book_title,
                    br.created_at,
                    br.updated_at,
                    (SELECT COUNT(*) FROM transaction_logs tl WHERE tl.transaction_id = br.id) as log_count
                  FROM book_requests br
                  JOIN books b ON br.book_id = b.id
                  JOIN users req ON br.requester_id = req.id
                  JOIN users own ON br.owner_id = own.id
                  LEFT JOIN books sb ON br.swap_book_id = sb.id
                  ORDER BY br.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = $row;
        }

        echo json_encode([
            "success" => true,
            "transactions" => $transactions
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Admin actions (force cancel, mark completed, etc.)
        $input = json_decode(file_get_contents("php://input"), true);
        $action = $input['action'] ?? null;
        $transaction_id = $input['transaction_id'] ?? null;
        $admin_id = $input['admin_id'] ?? null;

        if (!$action || !$transaction_id || !$admin_id) {
            throw new Exception("Action, Transaction ID and Admin ID required");
        }

        switch ($action) {
            case 'force_cancel':
                $update = $db->prepare("UPDATE book_requests SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
                $update->execute([$transaction_id]);
                
                $log = "INSERT INTO transaction_logs (transaction_id, action, description, performed_by, user_type) 
                        VALUES (?, 'admin_cancelled', 'Transaction cancelled by admin', ?, 'admin')";
                $log_stmt = $db->prepare($log);
                $log_stmt->execute([$transaction_id, $admin_id]);
                break;

            case 'mark_completed':
                $update = $db->prepare("UPDATE book_requests SET status = 'Completed', updated_at = NOW() WHERE id = ?");
                $update->execute([$transaction_id]);
                
                // Mark books as available again
                $get_books = $db->prepare("SELECT book_id, swap_book_id FROM book_requests WHERE id = ?");
                $get_books->execute([$transaction_id]);
                $books = $get_books->fetch(PDO::FETCH_ASSOC);
                
                $update_book = $db->prepare("UPDATE books SET status = 'Available' WHERE id = ?");
                $update_book->execute([$books['book_id']]);
                
                if ($books['swap_book_id']) {
                    $update_book->execute([$books['swap_book_id']]);
                }
                
                $log = "INSERT INTO transaction_logs (transaction_id, action, description, performed_by, user_type) 
                        VALUES (?, 'admin_completed', 'Transaction marked completed by admin', ?, 'admin')";
                $log_stmt = $db->prepare($log);
                $log_stmt->execute([$transaction_id, $admin_id]);
                break;

            default:
                throw new Exception("Invalid action");
        }

        echo json_encode([
            "success" => true,
            "message" => "Action completed successfully"
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>