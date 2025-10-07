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

    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception("User ID required");
    }

    $query = "SELECT 
                br.id,
                br.book_id,
                b.title as book_title,
                b.author as book_author,
                u.name as requester_name,
                u.email as requester_email,
                br.request_type,
                br.status,
                br.message,
                br.proposed_return_date,
                br.swap_book_id,
                sb.title as swap_book_title,
                br.created_at,
                br.updated_at
              FROM book_requests br
              JOIN books b ON br.book_id = b.id
              JOIN users u ON br.requester_id = u.id
              LEFT JOIN books sb ON br.swap_book_id = sb.id
              WHERE br.owner_id = ?
              ORDER BY br.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    $requests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $requests[] = $row;
    }

    echo json_encode([
        "success" => true,
        "requests" => $requests
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "requests" => []
    ]);
}
?>