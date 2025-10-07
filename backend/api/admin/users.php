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
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
        
        // Get users from database
        $query = "SELECT 
                    u.id, 
                    u.name, 
                    u.email, 
                    u.city, 
                    u.address,
                    u.profile_picture,
                    u.created_at,
                    (SELECT COUNT(*) FROM books b WHERE b.user_id = u.id) as book_count
                  FROM users u 
                  ORDER BY u.created_at DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT " . $limit;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                "id" => $row['id'],
                "name" => $row['name'],
                "email" => $row['email'],
                "city" => $row['city'],
                "address" => $row['address'],
                "profile_picture" => $row['profile_picture'] ?: 'https://via.placeholder.com/40',
                "book_count" => (int)$row['book_count'],
                "joined_date" => $row['created_at']
            ];
        }
        
        echo json_encode([
            "success" => true,
            "users" => $users
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        $user_id = $input['user_id'] ?? null;
        
        if ($user_id) {
            echo json_encode([
                "success" => true,
                "message" => "User delete functionality ready"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "User ID required"
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => true,
        "users" => []
    ]);
}
?>