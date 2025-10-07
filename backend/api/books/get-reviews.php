<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $book_id = $_GET['book_id'] ?? null;
    
    if (!$book_id) {
        throw new Exception("Book ID is required");
    }

    // Get reviews with user information
    $query = "SELECT 
                br.id,
                br.book_id,
                br.user_id,
                br.rating,
                br.comment,
                br.created_at,
                u.name as reviewer_name,
                u.profile_picture as reviewer_profile_picture
              FROM book_reviews br
              JOIN users u ON br.user_id = u.id
              WHERE br.book_id = ?
              ORDER BY br.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$book_id]);
    
    $reviews = [];
    $total_rating = 0;
    $review_count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = $row;
        $total_rating += $row['rating'];
        $review_count++;
    }

    // Calculate average rating
    $average_rating = $review_count > 0 ? $total_rating / $review_count : 0;

    echo json_encode([
        "success" => true,
        "reviews" => $reviews,
        "average_rating" => round($average_rating, 1),
        "total_reviews" => $review_count
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "reviews" => [],
        "average_rating" => 0,
        "total_reviews" => 0
    ]);
}
?>