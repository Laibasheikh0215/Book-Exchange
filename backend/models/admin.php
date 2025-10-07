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

    // Admin login
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

    // Check if username exists
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE username = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Create new admin
    public function create() {
        if($this->usernameExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password=:password,
                      role=:role, is_active=:is_active";
        
        $stmt = $this->conn->prepare($query);
        
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_active", $this->is_active);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
}
?>