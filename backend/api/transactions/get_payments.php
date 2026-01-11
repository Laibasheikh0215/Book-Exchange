<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$transaction_id = $_GET['transaction_id'] ?? '';

try {
    if (empty($transaction_id)) {
        $query = "SELECT tp.*, t.book_id, b.title, 
                         u1.name as borrower_name, u2.name as lender_name,
                         a.username as verified_by_name
                  FROM transaction_payments tp
                  JOIN transactions t ON tp.transaction_id = t.id
                  JOIN books b ON t.book_id = b.id
                  JOIN users u1 ON t.borrower_id = u1.id
                  JOIN users u2 ON t.lender_id = u2.id
                  LEFT JOIN admin_users a ON tp.verified_by = a.id
                  ORDER BY tp.payment_date DESC";
    } else {
        $query = "SELECT tp.*, t.book_id, b.title,
                         u1.name as borrower_name, u2.name as lender_name,
                         a.username as verified_by_name
                  FROM transaction_payments tp
                  JOIN transactions t ON tp.transaction_id = t.id
                  JOIN books b ON t.book_id = b.id
                  JOIN users u1 ON t.borrower_id = u1.id
                  JOIN users u2 ON t.lender_id = u2.id
                  LEFT JOIN admin_users a ON tp.verified_by = a.id
                  WHERE tp.transaction_id = :transaction_id
                  ORDER BY tp.payment_date DESC";
    }

    $stmt = $db->prepare($query);
    if (!empty($transaction_id)) {
        $stmt->bindParam(":transaction_id", $transaction_id);
    }
    $stmt->execute();

    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "payments" => $payments,
        "count" => count($payments)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>