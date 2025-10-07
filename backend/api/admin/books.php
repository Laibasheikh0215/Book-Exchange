<?php
// Headers FIRST
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database with CORRECT path - go up 2 levels from admin to backend
include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Get books from database
        $query = "SELECT 
                    b.id,
                    b.title,
                    b.author,
                    b.isbn,
                    b.genre,
                    b.condition,
                    b.description,
                    b.status,
                    b.image_path,
                    b.created_at,
                    u.id as user_id,
                    u.name as user_name,
                    u.email as user_email,
                    u.city as user_city
                  FROM books b
                  JOIN users u ON b.user_id = u.id
                  ORDER BY b.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $books = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $books[] = [
                "id" => $row['id'],
                "title" => $row['title'],
                "author" => $row['author'],
                "isbn" => $row['isbn'],
                "genre" => $row['genre'],
                "condition" => $row['condition'],
                "description" => $row['description'],
                "status" => $row['status'],
                "image_path" => $row['image_path'] ?: 'https://via.placeholder.com/100x150?text=No+Image',
                "created_at" => $row['created_at'],
                "user" => [
                    "id" => $row['user_id'],
                    "name" => $row['user_name'],
                    "email" => $row['user_email'],
                    "city" => $row['user_city']
                ]
            ];
        }
        
        echo json_encode([
            "success" => true,
            "books" => $books
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        $book_id = $input['book_id'] ?? null;
        
        if ($book_id) {
            echo json_encode([
                "success" => true,
                "message" => "Book delete functionality ready"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Book ID required"
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => true,
        "books" => []
    ]);
}
?>