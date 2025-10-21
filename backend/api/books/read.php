<?php
// ERROR REPORTING ON
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Include files
    include_once '../../config/database.php';
    include_once '../../models/Book.php';

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // ✅ CHECK IF SINGLE BOOK REQUEST
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        // Get single book by ID - WITH USERNAME JOIN
        $book_id = $_GET['id'];
        
        $query = "SELECT b.*, u.name as owner_name, u.email as owner_email 
                  FROM books b 
                  LEFT JOIN users u ON b.user_id = u.id 
                  WHERE b.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $book_id);
        $stmt->execute();
        
        $books_arr = array();
        $books_arr["books"] = array();

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Ensure all image fields are set
                $row['image_path'] = $row['image_path'] ?? null;
                $row['image_path2'] = $row['image_path2'] ?? null;
                $row['image_path3'] = $row['image_path3'] ?? null;
                $row['owner_name'] = $row['owner_name'] ?? 'User ' . $row['user_id'];
                $row['location'] = $row['location'] ?? 'Location not specified';
                
                array_push($books_arr["books"], $row);
            }
            
            http_response_code(200);
            echo json_encode($books_arr, JSON_PRETTY_PRINT);
            
        } else {
            http_response_code(404);
            echo json_encode(array(
                "success" => false,
                "message" => "Book not found"
            ));
        }
        
    } 
    // ✅ CHECK IF USER-SPECIFIC BOOKS REQUEST (FOR DASHBOARD)
    else if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        // Get books for specific user only
        $user_id = $_GET['user_id'];
        
        $query = "SELECT b.* FROM books b WHERE b.user_id = :user_id ORDER BY b.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $books_arr = array();
        $books_arr["books"] = array();

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Ensure all image fields are set
                $row['image_path'] = $row['image_path'] ?? null;
                $row['image_path2'] = $row['image_path2'] ?? null;
                $row['image_path3'] = $row['image_path3'] ?? null;
                $row['owner_name'] = 'You'; // Since it's user's own books
                
                array_push($books_arr["books"], $row);
            }
            
            http_response_code(200);
            echo json_encode($books_arr, JSON_PRETTY_PRINT);
            
        } else {
            // No books found for this user
            http_response_code(200);
            echo json_encode(array(
                "books" => array(),
                "message" => "You haven't added any books yet."
            ), JSON_PRETTY_PRINT);
        }
    }
    else {
        // Get all available books (for browse books page)
        $query = "SELECT b.* FROM books b WHERE b.status = 'Available' ORDER BY b.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $books_arr = array();
        $books_arr["books"] = array();

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Ensure all image fields are set
                $row['image_path'] = $row['image_path'] ?? null;
                $row['image_path2'] = $row['image_path2'] ?? null;
                $row['image_path3'] = $row['image_path3'] ?? null;
                $row['owner_name'] = 'User ' . $row['user_id'];
                
                array_push($books_arr["books"], $row);
            }
            
            http_response_code(200);
            echo json_encode($books_arr, JSON_PRETTY_PRINT);
            
        } else {
            // No books found
            http_response_code(200);
            echo json_encode(array(
                "books" => array(),
                "message" => "No books found in database."
            ), JSON_PRETTY_PRINT);
        }
    }

} catch (Exception $e) {
    // Return error as JSON
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Server Error: " . $e->getMessage()
    ), JSON_PRETTY_PRINT);
}
?>