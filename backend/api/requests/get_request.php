<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$request_id = $_GET['id'] ?? '';

if (empty($request_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Request ID is required"]);
    exit();
}

try {
    $query = "SELECT br.*, b.title as book_title, b.price as book_price, 
                     b.user_id as owner_id, u.name as owner_name,
                     u2.name as requester_name
              FROM book_requests br 
              JOIN books b ON br.book_id = b.id 
              JOIN users u ON br.owner_id = u.id
              JOIN users u2 ON br.requester_id = u2.id
              WHERE br.id = :request_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":request_id", $request_id);
    $stmt->execute();

    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        echo json_encode([
            "success" => true,
            "request" => $request
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Request not found"
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