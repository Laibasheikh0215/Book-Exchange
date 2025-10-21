<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Review.php';

$database = new Database();
$db = $database->getConnection();

$review = new Review($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->book_id) && !empty($data->user_id) && !empty($data->rating) && !empty($data->comment)) {
    $review->book_id = $data->book_id;
    $review->user_id = $data->user_id;
    $review->rating = $data->rating;
    $review->comment = $data->comment;

    if($review->create()) {
        http_response_code(201);
        echo json_encode(array("message" => "Review submitted successfully."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to submit review."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to submit review. Data is incomplete."));
}
?>