<?php
class Admin {
    private $conn;
    private $table_name = "admin_users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Admin login method
    public function login() {
        $query = "SELECT id, username, email, password, role, is_active
                FROM " . $this->table_name . "
                WHERE username = ? AND is_active = 1
                LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                
                // Update last login
                $this->updateLastLogin();
                
                return true;
            }
        }
        
        return false;
    }

    // Update last login
    private function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_login = NOW() 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
    }

    // Log admin action
    public function logAction($action, $description) {
        $query = "INSERT INTO admin_logs 
                  SET admin_id=:admin_id, action=:action, description=:description,
                      ip_address=:ip_address, user_agent=:user_agent";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":admin_id", $this->id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":ip_address", $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(":user_agent", $_SERVER['HTTP_USER_AGENT']);
        
        return $stmt->execute();
    }
}
?>