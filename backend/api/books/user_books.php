<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception("User ID is required");
    }

    // Get user's available books
    $query = "SELECT 
                id,
                title,
                author,
                genre,
                `condition`,
                status,
                image_path
              FROM books 
              WHERE user_id = ? AND status = 'Available'
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    $books = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books[] = $row;
    }

    echo json_encode([
        "success" => true,
        "books" => $books
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "books" => []
    ]);
}
?>