<?php
class User {
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $name;
    public $email;
    public $password;
    public $address;
    public $city;
    public $state;
    public $zip_code;
    public $latitude;
    public $longitude;
    public $profile_picture;
    public $created_at;
    public $updated_at;

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if email already exists
    public function emailExists() {
        $query = "SELECT id, name, password, profile_picture, email
                FROM " . $this->table_name . "
                WHERE email = ?
                LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->profile_picture = $row['profile_picture'];
            $this->email = $row['email'];
            
            return true;
        }
        
        return false;
    }

    // Create new user
    public function create() {
        // First check if email already exists
        if ($this->emailExists()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, email=:email, password=:password,
                      address=:address,
                      city=:city, state=:state, zip_code=:zip_code,
                      latitude=:latitude,
                      longitude=:longitude, profile_picture=:profile_picture,
                      created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->zip_code = htmlspecialchars(strip_tags($this->zip_code));
        
        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":state", $this->state);
        $stmt->bindParam(":zip_code", $this->zip_code);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":profile_picture", $this->profile_picture);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Get user by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->state = $row['state'];
            $this->zip_code = $row['zip_code'];
            $this->latitude = $row['latitude'];
            $this->longitude = $row['longitude'];
            $this->profile_picture = $row['profile_picture'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }

    // Update user information
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, email=:email,
                      address=:address, city=:city,
                      state=:state, zip_code=:zip_code,
                      latitude=:latitude, longitude=:longitude,
                      profile_picture=:profile_picture, updated_at=NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->zip_code = htmlspecialchars(strip_tags($this->zip_code));
        
        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":state", $this->state);
        $stmt->bindParam(":zip_code", $this->zip_code);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":profile_picture", $this->profile_picture);
        $stmt->bindParam(":id", $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Update user password
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " 
                  SET password=:password, updated_at=NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash new password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Bind parameters
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Get all users
    public function readAll() {
        $query = "SELECT id, name, email, city, country, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Search users
    public function search($keywords) {
        $query = "SELECT id, name, email, city, country, created_at 
                  FROM " . $this->table_name . " 
                  WHERE name LIKE ? OR email LIKE ? OR user_id LIKE ?
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        
        $stmt->execute();
        
        return $stmt;
    }

    // Verify password for login
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
}
?>