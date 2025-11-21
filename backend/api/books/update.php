<?php
// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';
include_once '../../models/Book.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get book ID
    $book_id = $_POST['id'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if (empty($book_id) || empty($user_id)) {
        throw new Exception("Book ID and User ID are required");
    }

    // Verify book belongs to user
    $verify_query = "SELECT user_id FROM books WHERE id = :id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(":id", $book_id);
    $verify_stmt->execute();

    $book = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$book || $book['user_id'] != $user_id) {
        throw new Exception("You can only edit your own books");
    }

    // Handle file uploads
    $upload_dir = "../../uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_path = '';
    $image_path2 = '';
    $image_path3 = '';

    // Process new uploaded images
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] == 0) {
            $file = $_FILES["image$i"];
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = "book_" . uniqid() . "_$i." . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                if ($i == 1) $image_path = "uploads/" . $file_name;
                if ($i == 2) $image_path2 = "uploads/" . $file_name;
                if ($i == 3) $image_path3 = "uploads/" . $file_name;
            }
        }
    }

    // Get other form data
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $price = $_POST['price'] ?? '0.00'; // ✅ YEH NAYA PRICE FIELD
    $condition = $_POST['condition'] ?? '';
    $status = $_POST['status'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate required fields
    if (empty($title) || empty($author) || empty($condition)) {
        throw new Exception("Title, author, and condition are required");
    }

    // Validate price
    $price = floatval($price);
    if ($price < 0) {
        throw new Exception("Price cannot be negative");
    }

    // Build update query
    $update_fields = [];
    $params = [];

    $update_fields[] = "title = :title";
    $params[':title'] = $title;

    $update_fields[] = "author = :author";
    $params[':author'] = $author;

    $update_fields[] = "isbn = :isbn";
    $params[':isbn'] = $isbn;

    $update_fields[] = "genre = :genre";
    $params[':genre'] = $genre;

    // ✅ YEH NAYA PRICE FIELD ADD KAREN
    $update_fields[] = "price = :price";
    $params[':price'] = $price;

    $update_fields[] = "`condition` = :condition";
    $params[':condition'] = $condition;

    $update_fields[] = "status = :status";
    $params[':status'] = $status;

    $update_fields[] = "description = :description";
    $params[':description'] = $description;

    $update_fields[] = "updated_at = NOW()";

    // Add image updates if new images were uploaded
    if (!empty($image_path)) {
        $update_fields[] = "image_path = :image_path";
        $params[':image_path'] = $image_path;
    }

    if (!empty($image_path2)) {
        $update_fields[] = "image_path2 = :image_path2";
        $params[':image_path2'] = $image_path2;
    }

    if (!empty($image_path3)) {
        $update_fields[] = "image_path3 = :image_path3";
        $params[':image_path3'] = $image_path3;
    }

    $update_query = "UPDATE books SET " . implode(", ", $update_fields) . " WHERE id = :id AND user_id = :user_id";
    $params[':id'] = $book_id;
    $params[':user_id'] = $user_id;

    $stmt = $db->prepare($update_query);
    
    if ($stmt->execute($params)) {
        echo json_encode([
            "success" => true,
            "message" => "Book updated successfully",
            "price" => $price // ✅ Optional: Return price in response
        ]);
    } else {
        throw new Exception("Failed to update book");
    }

} catch (Exception $e) {
    error_log("❌ Error in update.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>