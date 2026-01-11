<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT t.*, b.title as book_title, b.image_path as book_cover, 
                     u.name as lender_name, u2.name as borrower_name,
                     br.request_type
              FROM transactions t 
              JOIN books b ON t.book_id = b.id 
              JOIN users u ON t.lender_id = u.id 
              JOIN users u2 ON t.borrower_id = u2.id
              JOIN book_requests br ON t.request_id = br.id
              ORDER BY t.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Check if we got data
    if (empty($transactions)) {
        error_log("No transactions found in database");
    } else {
        error_log("Found " . count($transactions) . " transactions");
    }

    echo json_encode([
        "success" => true,
        "transactions" => $transactions,
        "count" => count($transactions)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>