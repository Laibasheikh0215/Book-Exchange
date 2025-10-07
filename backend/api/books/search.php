<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../config/database.php';
include_once '../../models/Book.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if specific book ID is requested
    $book_id = $_GET['id'] ?? null;
    
    if ($book_id) {
        // Get single book with owner details
        $query = "SELECT 
                    b.*,
                    u.name as owner_name,
                    u.profile_picture as owner_profile_picture,
                    u.city as location
                  FROM books b
                  JOIN users u ON b.user_id = u.id
                  WHERE b.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$book_id]);
        
        if ($stmt->rowCount() > 0) {
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                "success" => true,
                "book" => $book
            ]);
        } else {
            throw new Exception("Book not found");
        }
        
    } else {
        // Get all books (existing code)
        $query = "SELECT 
                    b.*,
                    u.name as owner_name,
                    u.city as location
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
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>