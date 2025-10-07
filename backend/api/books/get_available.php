<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $query = "SELECT 
                b.id,
                b.title,
                b.author,
                b.genre,
                b.condition,
                b.description,
                b.status,
                b.image_path,
                u.id as user_id,
                u.name as user_name,
                u.city as user_city
              FROM books b
              JOIN users u ON b.user_id = u.id
              WHERE b.status = 'Available'
              ORDER BY b.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $books = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books[] = $row;
    }

    echo json_encode([
        "success" => true,
        "books" => $books
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "books" => []
    ]);
}
?>