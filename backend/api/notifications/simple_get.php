<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Simple direct connection
$host = "localhost";
$db_name = "book_exchange";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
    
    // Simple query
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo json_encode([
            "success" => true,
            "notifications" => $notifications,
            "count" => count($notifications)
        ]);
    } else {
        // Return empty array
        echo json_encode([
            "success" => true,
            "notifications" => [],
            "count" => 0,
            "message" => "No notifications found"
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "notifications" => []
    ]);
}
?>