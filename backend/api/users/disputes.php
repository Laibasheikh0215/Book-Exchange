<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

class UserDispute {
    private $conn;
    private $table = 'disputes';

    public function __construct($db) {
        $this->conn = $db;
    }

    // ✅ FIXED: File a new dispute - SIMPLE & WORKING
    public function fileDispute($data) {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['description']) || empty($data['category']) || 
                empty($data['complainant_id']) || empty($data['respondent_id'])) {
                return ['success' => false, 'message' => "All required fields must be filled"];
            }

            // ✅ FIRST CHECK if respondent user exists
            $checkUserQuery = "SELECT id FROM users WHERE id = ?";
            $checkStmt = $this->conn->prepare($checkUserQuery);
            $checkStmt->execute([$data['respondent_id']]);
            
            if (!$checkStmt->fetch()) {
                return [
                    'success' => false, 
                    'message' => 'Invalid User ID: The user you selected does not exist in our system.'
                ];
            }

            // ✅ SIMPLIFIED INSERT QUERY
            $query = "INSERT INTO " . $this->table . " 
                      (title, description, category, priority, status, complainant_id, respondent_id, transaction_id, evidence, desired_resolution, created_at) 
                      VALUES (:title, :description, :category, :priority, 'open', :complainant_id, :respondent_id, :transaction_id, :evidence, :desired_resolution, NOW())";

            $stmt = $this->conn->prepare($query);
            
            // ✅ BIND PARAMETERS CORRECTLY
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':priority', $data['priority']);
            $stmt->bindParam(':complainant_id', $data['complainant_id'], PDO::PARAM_INT);
            $stmt->bindParam(':respondent_id', $data['respondent_id'], PDO::PARAM_INT);
            
            // Optional fields
            $transaction_id = !empty($data['transaction_id']) ? $data['transaction_id'] : null;
            $evidence = !empty($data['evidence']) ? $data['evidence'] : null;
            $desired_resolution = !empty($data['desired_resolution']) ? $data['desired_resolution'] : null;
            
            $stmt->bindParam(':transaction_id', $transaction_id);
            $stmt->bindParam(':evidence', $evidence);
            $stmt->bindParam(':desired_resolution', $desired_resolution);

            if ($stmt->execute()) {
                $dispute_id = $this->conn->lastInsertId();
                
                // ✅ Create notification for admins
                $this->notifyAdmins($dispute_id, $data['title']);
                
                return [
                    'success' => true,
                    'message' => 'Dispute filed successfully',
                    'dispute_id' => $dispute_id
                ];
            } else {
                $errorInfo = $stmt->errorInfo();
                return [
                    'success' => false,
                    'message' => 'Failed to file dispute: ' . $errorInfo[2]
                ];
            }

        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // ✅ FIXED: Notify admins about new dispute
    private function notifyAdmins($dispute_id, $title) {
        try {
            $query = "INSERT INTO admin_notifications (dispute_id, title, message, is_read, created_at) 
                      VALUES (?, ?, ?, 0, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $message = "New dispute filed: " . $title;
            $stmt->execute([$dispute_id, $title, $message]);
            
        } catch (PDOException $e) {
            error_log("Notification Error: " . $e->getMessage());
        }
    }
  
    // ✅ FIXED: Get user's disputes
    public function getUserDisputes($user_id) {
        try {
            $query = "SELECT d.*, 
                             c.name as complainant_name, 
                             r.name as respondent_name,
                             a.username as assigned_admin_name
                      FROM " . $this->table . " d
                      LEFT JOIN users c ON d.complainant_id = c.id
                      LEFT JOIN users r ON d.respondent_id = r.id
                      LEFT JOIN admin_users a ON d.assigned_admin_id = a.id
                      WHERE d.complainant_id = ? OR d.respondent_id = ?
                      ORDER BY d.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $user_id]);

            $disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'disputes' => $disputes
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching disputes: ' . $e->getMessage()
            ];
        }
    }
}

// ✅ Handle requests
$database = new Database();
$db = $database->getConnection();
$userDispute = new UserDispute($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result = $userDispute->fileDispute($input);
    echo json_encode($result);
} 
elseif ($method == 'GET') {
    if (isset($_GET['user_id'])) {
        $result = $userDispute->getUserDisputes($_GET['user_id']);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
    }
} 
else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>