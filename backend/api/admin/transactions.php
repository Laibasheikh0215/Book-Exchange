<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Query to get all transactions with user and book details
    $query = "
        SELECT 
            t.*,
            b.title as book_title,
            b.cover_image as book_cover,
            u1.name as borrower_name,
            u2.name as lender_name
        FROM transactions t
        LEFT JOIN books b ON t.book_id = b.id
        LEFT JOIN users u1 ON t.borrower_id = u1.id
        LEFT JOIN users u2 ON t.lender_id = u2.id
        ORDER BY t.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'count' => count($transactions)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>