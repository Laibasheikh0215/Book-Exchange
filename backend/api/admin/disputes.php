<?php
// Headers FIRST
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET");
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

    // Check if disputes table exists
    $check_table = $db->prepare("SHOW TABLES LIKE 'disputes'");
    $check_table->execute();
    
    $disputes = [];
    if ($check_table->rowCount() > 0) {
        // Get disputes from database
        $query = "SELECT 
                    d.id,
                    d.title,
                    d.description,
                    d.priority,
                    d.status,
                    d.created_at,
                    uc.name as complainant_name,
                    ur.name as respondent_name
                  FROM disputes d
                  JOIN users uc ON d.complainant_id = uc.id
                  JOIN users ur ON d.respondent_id = ur.id
                  ORDER BY d.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $disputes[] = [
                "id" => $row['id'],
                "title" => $row['title'],
                "description" => $row['description'],
                "complainant_name" => $row['complainant_name'],
                "respondent_name" => $row['respondent_name'],
                "priority" => $row['priority'],
                "status" => $row['status'],
                "created_at" => $row['created_at'],
                "assigned_admin_name" => "Unassigned"
            ];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
        
        if ($limit) {
            $limited_disputes = array_slice($disputes, 0, $limit);
            echo json_encode([
                "success" => true,
                "disputes" => $limited_disputes
            ]);
        } else {
            echo json_encode([
                "success" => true, 
                "disputes" => $disputes
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => true,
        "disputes" => []
    ]);
}
?>