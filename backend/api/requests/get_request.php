<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : die();

try {
    $query = "SELECT br.id, br.book_id, b.title as book_title, 
                     br.requester_id, u1.name as requester_name,
                     br.owner_id, u2.name as owner_name,
                     br.status, br.request_type, br.message, br.proposed_return_date,
                     br.created_at
              FROM book_requests br
              JOIN books b ON br.book_id = b.id
              JOIN users u1 ON br.requester_id = u1.id
              JOIN users u2 ON br.owner_id = u2.id
              WHERE br.id = ?";

    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $request_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $request_details = array(
            "id" => $row['id'],
            "book_id" => $row['book_id'],
            "book_title" => $row['book_title'],
            "requester_id" => $row['requester_id'],
            "requester_name" => $row['requester_name'],
            "owner_id" => $row['owner_id'],
            "owner_name" => $row['owner_name'],
            "status" => $row['status'],
            "request_type" => $row['request_type'],
            "message" => $row['message'],
            "proposed_return_date" => $row['proposed_return_date'],
            "created_at" => $row['created_at']
        );
        
        http_response_code(200);
        echo json_encode($request_details);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Request not found."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving request: " . $e->getMessage()));
}
?>