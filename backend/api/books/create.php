<?php
// project/backend/api/books/create.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");  // Allow POST and OPTIONS
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST method for actual requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed. Use POST method."
    ]);
    exit();
}

// Start output buffering
ob_start();

try {
    // Include database and model
    include_once '../../config/database.php';
    include_once '../../models/Book.php';

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // UPLOADS FOLDER - ABSOLUTE PATH
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/project/uploads/';
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }

    // Check permissions
    if (!is_writable($upload_dir)) {
        throw new Exception("Upload directory is not writable");
    }

    $image_path = '';
    $image_path2 = '';
    $image_path3 = '';

    // Handle uploaded files
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] == 0) {
            $file = $_FILES["image$i"];
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = "book_" . uniqid() . "_$i." . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $saved_path = "uploads/" . $file_name;
                if ($i == 1) $image_path = $saved_path;
                if ($i == 2) $image_path2 = $saved_path;
                if ($i == 3) $image_path3 = $saved_path;
            }
        }
    }

    // Handle predefined images
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_POST["predefined_image$i"]) && !empty($_POST["predefined_image$i"])) {
            $predefined_image = $_POST["predefined_image$i"];
            if ($i == 1 && empty($image_path)) $image_path = $predefined_image;
            if ($i == 2 && empty($image_path2)) $image_path2 = $predefined_image;
            if ($i == 3 && empty($image_path3)) $image_path3 = $predefined_image;
        }
    }

    // Get form data
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $condition = $_POST['condition'] ?? 'Good';
    $description = $_POST['description'] ?? '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.00;

    // Validate required fields
    if (empty($title) || empty($author) || empty($user_id)) {
        throw new Exception("Missing required fields: title, author, user_id");
    }

    $book = new Book($db);
    
    // Set book properties
    $book->title = $title;
    $book->author = $author;
    $book->user_id = $user_id;
    $book->isbn = $isbn;
    $book->genre = $genre;
    $book->condition = $condition;
    $book->price = $price;
    $book->description = $description;
    $book->image_path = $image_path;
    $book->image_path2 = $image_path2;
    $book->image_path3 = $image_path3;

    if ($book->create()) {
        $response = [
            "success" => true,
            "message" => "Book added successfully",
            "book_id" => $book->id
        ];
        
        ob_end_clean();
        echo json_encode($response);
    } else {
        throw new Exception("Failed to create book in database");
    }

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>