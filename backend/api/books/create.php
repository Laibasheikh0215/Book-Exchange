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

    // ✅ UPLOADS FOLDER BANAYEIN - ABSOLUTE PATH USE KAREIN
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/project/uploads/';
    error_log("📁 Upload directory: " . $upload_dir);
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Failed to create upload directory");
        }
        error_log("✅ Upload directory created");
    }

    // ✅ CHECK PERMISSIONS
    if (!is_writable($upload_dir)) {
        throw new Exception("Upload directory is not writable");
    }
    error_log("✅ Upload directory is writable");

    $image_path = '';
    $image_path2 = '';
    $image_path3 = '';

    // ✅ DEBUG FILES
    error_log("📦 Files received: " . print_r($_FILES, true));
    error_log("📦 Post data: " . print_r($_POST, true));

    // ✅ UPLOADED FILES HANDLE KAREIN
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] == 0) {
            $file = $_FILES["image$i"];
            error_log("📸 Processing image $i: " . $file['name']);
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = "book_" . uniqid() . "_$i." . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            error_log("💾 Saving to: " . $file_path);
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $saved_path = "uploads/" . $file_name;
                if ($i == 1) $image_path = $saved_path;
                if ($i == 2) $image_path2 = $saved_path;
                if ($i == 3) $image_path3 = $saved_path;
                error_log("✅ File uploaded successfully: " . $saved_path);
            } else {
                error_log("❌ File upload failed for: " . $file['name']);
                error_log("❌ Upload error: " . $file['error']);
            }
        } else {
            if (isset($_FILES["image$i"])) {
                error_log("❌ File error for image$i: " . $_FILES["image$i"]['error']);
            }
        }
    }

    // ✅ PREDEFINED IMAGES HANDLE KAREIN
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_POST["predefined_image$i"]) && !empty($_POST["predefined_image$i"])) {
            $predefined_image = $_POST["predefined_image$i"];
            if ($i == 1 && empty($image_path)) $image_path = $predefined_image;
            if ($i == 2 && empty($image_path2)) $image_path2 = $predefined_image;
            if ($i == 3 && empty($image_path3)) $image_path3 = $predefined_image;
            error_log("📸 Using predefined image $i: " . $predefined_image);
        }
    }

    // ✅ FORM DATA GET KAREIN
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $condition = $_POST['condition'] ?? 'Good';
    $description = $_POST['description'] ?? '';

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
    $book->description = $description;
    $book->image_path = $image_path;
    $book->image_path2 = $image_path2;
    $book->image_path3 = $image_path3;

    error_log("🎯 Creating book: " . $book->title);
    error_log("📸 Final image paths: " . $image_path . ", " . $image_path2 . ", " . $image_path3);

    if ($book->create()) {
        $response = [
            "success" => true,
            "message" => "Book added successfully",
            "book_id" => $book->id
        ];
        error_log("✅ Book created successfully with ID: " . $book->id);
        
        echo json_encode($response);
    } else {
        throw new Exception("Failed to create book in database");
    }

} catch (Exception $e) {
    error_log("❌ Error in create.php: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>