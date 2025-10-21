<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Review.php';

$database = new Database();
$db = $database->getConnection();

$review = new Review($db);

$book_id = isset($_GET['book_id']) ? $_GET['book_id'] : die();

$average_rating = $review->getAverageRating($book_id);

echo json_encode(array("average_rating" => $average_rating));
?>