<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$request_id = $_GET['request_id'] ?? '';

if (empty($request_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Request ID is required"]);
    exit();
}

try {
    // Get transaction by request ID
    $query = "SELECT t.*, b.title as book_title, b.price, b.image_path,
                     u1.name as borrower_name, u1.email as borrower_email,
                     u2.name as lender_name, u2.email as lender_email,
                     br.request_type, br.message as request_message
              FROM transactions t
              JOIN books b ON t.book_id = b.id
              JOIN users u1 ON t.borrower_id = u1.id
              JOIN users u2 ON t.lender_id = u2.id
              JOIN book_requests br ON t.request_id = br.id
              WHERE t.request_id = :request_id
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":request_id", $request_id);
    $stmt->execute();

    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        echo json_encode([
            "success" => true,
            "transaction" => $transaction
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No transaction found for this request"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>