<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Review.php';

$database = new Database();
$db = $database->getConnection();

$review = new Review($db);

$book_id = isset($_GET['book_id']) ? $_GET['book_id'] : die();

$stmt = $review->getReviewsByBook($book_id);
$num = $stmt->rowCount();

if($num > 0){
    $reviews_arr = array();
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $review_item = array(
            "id" => $row['id'],
            "book_id" => $row['book_id'],
            "user_id" => $row['user_id'],
            "user_name" => $row['user_name'],
            "user_avatar" => $row['profile_picture'],
            "rating" => $row['rating'],
            "comment" => $row['comment'],
            "created_at" => $row['created_at']
        );
        array_push($reviews_arr, $review_item);
    }
    
    http_response_code(200);
    echo json_encode($reviews_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No reviews found."));
}
?>