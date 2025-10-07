<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $transaction_id = $_GET['transaction_id'] ?? null;
    
    if (!$transaction_id) {
        throw new Exception("Transaction ID required");
    }

    $query = "SELECT 
                tl.id,
                tl.action,
                tl.description,
                tl.user_type,
                tl.performed_by,
                u.name as performed_by_name,
                tl.created_at
              FROM transaction_logs tl
              LEFT JOIN users u ON tl.performed_by = u.id
              WHERE tl.transaction_id = ?
              ORDER BY tl.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$transaction_id]);
    
    $logs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $logs[] = $row;
    }

    echo json_encode([
        "success" => true,
        "logs" => $logs
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "logs" => []
    ]);
}
?>