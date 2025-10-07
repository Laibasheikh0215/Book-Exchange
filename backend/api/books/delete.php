<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Book.php';

$database = new Database();
$db = $database->getConnection();

$book = new Book($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->user_id)) {
    $book->id = $data->id;
    $book->user_id = $data->user_id;
    
    if ($book->delete()) {
        http_response_code(200);
        echo json_encode(array("message" => "Book was deleted."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to delete book."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to delete book. Data is incomplete."));
}
?>