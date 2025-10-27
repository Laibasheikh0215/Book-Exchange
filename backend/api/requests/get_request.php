<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$request_id = isset($_GET['id']) ? $_GET['id'] : null;

if($request_id) {
    $query = "SELECT r.*, 
                     b.title as book_title, b.author as book_author, b.image_path as book_image,
                     requester.name as requester_name, owner.name as owner_name
              FROM book_requests r
              LEFT JOIN books b ON r.book_id = b.id
              LEFT JOIN users requester ON r.requester_id = requester.id
              LEFT JOIN users owner ON r.owner_id = owner.id
              WHERE r.id = :id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $request_id);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $request_data = array(
            "id" => $row['id'],
            "book_id" => $row['book_id'],
            "book_title" => $row['book_title'],
            "book_author" => $row['book_author'],
            "book_image" => $row['book_image'],
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
        
        echo json_encode($request_data);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Request not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Request ID is required."));
}
?>