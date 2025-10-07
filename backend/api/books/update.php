<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
    
    // Get existing book data first
    if ($book->readOne()) {
        $book->title = $data->title ?? $book->title;
        $book->author = $data->author ?? $book->author;
        $book->isbn = $data->isbn ?? $book->isbn;
        $book->genre = $data->genre ?? $book->genre;
        $book->condition = $data->condition ?? $book->condition;
        $book->description = $data->description ?? $book->description;
        $book->status = $data->status ?? $book->status;
        
        if ($book->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Book was updated."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update book."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Book not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to update book. Data is incomplete."));
}
?>