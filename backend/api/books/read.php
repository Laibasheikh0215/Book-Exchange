<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Book.php';

$database = new Database();
$db = $database->getConnection();

$book = new Book($db);

// Get user ID from query parameter or all books
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if ($user_id) {
    $stmt = $book->readByUser($user_id);
} else {
    $stmt = $book->readAll();
}

$num = $stmt->rowCount();

if ($num > 0) {
    $books_arr = array();
    $books_arr["books"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $book_item = array(
            "id" => $id,
            "user_id" => $user_id,
            "title" => $title,
            "author" => $author,
            "isbn" => $isbn,
            "genre" => $genre,
            "condition" => $condition,
            "description" => $description,
            "status" => $status,
            "image_path" => $image_path,
            "created_at" => $created_at
        );
        
        array_push($books_arr["books"], $book_item);
    }
    
    http_response_code(200);
    echo json_encode($books_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No books found."));
}
?>