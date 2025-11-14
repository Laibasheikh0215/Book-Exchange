<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

class Users {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all users (except current user)
    public function getUsers($current_user_id) {
        try {
            $query = "SELECT id, name, email, city FROM " . $this->table . " 
                      WHERE id != ? AND status = 'active' 
                      ORDER BY name ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$current_user_id]);

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'users' => $users
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ];
        }
    }
}

// Handle request
$database = new Database();
$db = $database->getConnection();
$users = new Users($db);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $current_user_id = $_GET['current_user_id'] ?? 0;
    $result = $users->getUsers($current_user_id);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>