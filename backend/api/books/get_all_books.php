<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// ✅ CORRECT PATH FOR DATABASE CONNECTION
include_once '../../config/database.php';  // Changed from '../config/database.php'

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // ✅ SARI BOOKS FETCH KAREN
    $query = "SELECT 
                id,
                user_id, 
                title,
                author,
                genre,
                `condition`,
                description,
                status,
                image_path,
                location,
                created_at
              FROM books 
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "count" => count($books),
        "books" => $books
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>