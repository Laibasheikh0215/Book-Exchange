<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

class DisputeManagement {
    private $conn;
    private $table = 'disputes';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all disputes with filtering
    public function getDisputes($filter = 'all', $limit = null) {
        try {
            $query = "SELECT d.*, 
                             c.name as complainant_name, 
                             c.email as complainant_email,
                             r.name as respondent_name, 
                             r.email as respondent_email,
                             a.username as assigned_admin_name
                      FROM " . $this->table . " d
                      LEFT JOIN users c ON d.complainant_id = c.id
                      LEFT JOIN users r ON d.respondent_id = r.id
                      LEFT JOIN admin_users a ON d.assigned_admin_id = a.id
                      WHERE 1=1";

            // Apply filters
            if ($filter !== 'all') {
                switch ($filter) {
                    case 'open':
                        $query .= " AND d.status = 'open'";
                        break;
                    case 'under_review':
                        $query .= " AND d.status = 'under_review'";
                        break;
                    case 'resolved':
                        $query .= " AND d.status = 'resolved'";
                        break;
                    case 'urgent':
                        $query .= " AND d.priority = 'urgent'";
                        break;
                }
            }

            $query .= " ORDER BY d.created_at DESC";

            if ($limit) {
                $query .= " LIMIT " . $limit;
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'disputes' => $disputes,
                'count' => count($disputes)
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching disputes: ' . $e->getMessage()
            ];
        }
    }

    // Get single dispute by ID
    public function getDispute($id) {
        try {
            $query = "SELECT d.*, 
                             c.name as complainant_name, 
                             c.email as complainant_email,
                             r.name as respondent_name, 
                             r.email as respondent_email,
                             a.username as assigned_admin_name
                      FROM " . $this->table . " d
                      LEFT JOIN users c ON d.complainant_id = c.id
                      LEFT JOIN users r ON d.respondent_id = r.id
                      LEFT JOIN admin_users a ON d.assigned_admin_id = a.id
                      WHERE d.id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);

            $dispute = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dispute) {
                return [
                    'success' => true,
                    'dispute' => $dispute
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Dispute not found'
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching dispute: ' . $e->getMessage()
            ];
        }
    }

    // Update dispute (assign, start review, resolve)
    public function updateDispute($data) {
        try {
            $action = $data['action'] ?? '';
            $dispute_id = $data['dispute_id'] ?? '';
            $admin_id = $data['admin_id'] ?? '';

            if (empty($action) || empty($dispute_id)) {
                return ['success' => false, 'message' => 'Missing required fields'];
            }

            switch ($action) {
                case 'assign':
                    return $this->assignDispute($dispute_id, $admin_id);
                
                case 'start_review':
                    return $this->startReview($dispute_id, $admin_id);
                
                case 'resolve':
                    return $this->resolveDispute($data);
                
                default:
                    return ['success' => false, 'message' => 'Invalid action'];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating dispute: ' . $e->getMessage()
            ];
        }
    }

    // Assign dispute to admin
    private function assignDispute($dispute_id, $admin_id) {
        $query = "UPDATE " . $this->table . " 
                  SET assigned_admin_id = ?, status = 'under_review', updated_at = NOW() 
                  WHERE id = ? AND (assigned_admin_id IS NULL OR assigned_admin_id = ?)";

        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$admin_id, $dispute_id, $admin_id])) {
            return [
                'success' => true,
                'message' => 'Dispute assigned successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to assign dispute'
            ];
        }
    }

    // Start review
    private function startReview($dispute_id, $admin_id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'under_review', updated_at = NOW() 
                  WHERE id = ? AND assigned_admin_id = ? AND status = 'open'";

        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$dispute_id, $admin_id])) {
            return [
                'success' => true,
                'message' => 'Dispute review started'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to start review'
            ];
        }
    }

    // Resolve dispute
    private function resolveDispute($data) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'resolved', 
                      resolution = ?, 
                      resolution_notes = ?, 
                      penalty = ?, 
                      penalty_description = ?,
                      resolved_at = NOW(),
                      updated_at = NOW()
                  WHERE id = ? AND assigned_admin_id = ?";

        $stmt = $this->conn->prepare($query);
        
        $params = [
            $data['resolution'] ?? '',
            $data['resolution_notes'] ?? '',
            $data['penalty'] ?? 'none',
            $data['penalty_description'] ?? '',
            $data['dispute_id'],
            $data['admin_id']
        ];

        if ($stmt->execute($params)) {
            // Create notification for users
            $this->createResolutionNotification($data);
            
            return [
                'success' => true,
                'message' => 'Dispute resolved successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to resolve dispute'
            ];
        }
    }

    // Create notification for dispute resolution
    private function createResolutionNotification($data) {
        try {
            // Get dispute details
            $dispute_query = "SELECT complainant_id, respondent_id, title FROM disputes WHERE id = ?";
            $stmt = $this->conn->prepare($dispute_query);
            $stmt->execute([$data['dispute_id']]);
            $dispute = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dispute) {
                $notification_data = [
                    'user_id' => $dispute['complainant_id'],
                    'title' => 'Dispute Resolved',
                    'message' => "Your dispute '{$dispute['title']}' has been resolved. Resolution: {$data['resolution_notes']}",
                    'type' => 'System'
                ];
                $this->createUserNotification($notification_data);

                $notification_data['user_id'] = $dispute['respondent_id'];
                $this->createUserNotification($notification_data);
            }
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log("Error creating resolution notification: " . $e->getMessage());
        }
    }

    private function createUserNotification($data) {
        $query = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['type']
        ]);
    }
}

// Handle requests
$database = new Database();
$db = $database->getConnection();
$disputeManager = new DisputeManagement($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $result = $disputeManager->getDispute($_GET['id']);
        } else {
            $filter = $_GET['filter'] ?? 'all';
            $limit = $_GET['limit'] ?? null;
            $result = $disputeManager->getDisputes($filter, $limit);
        }
        echo json_encode($result);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $disputeManager->updateDispute($input);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>