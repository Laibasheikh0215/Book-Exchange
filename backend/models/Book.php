<?php
class Book {
    private $conn;
    private $table_name = "books";

    public $id;
    public $user_id;
    public $title;
    public $author;
    public $isbn;
    public $genre;
    public $condition;
    public $description;
    public $status;
    public $image_path;
    public $location;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // FIXED: Added backticks around 'condition' (reserved keyword)
        $query = "INSERT INTO " . $this->table_name . "
                SET user_id=:user_id, title=:title, author=:author, 
                isbn=:isbn, genre=:genre, `condition`=:condition, 
                description=:description, status='Available'";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->author = htmlspecialchars(strip_tags($this->author));
        $this->isbn = htmlspecialchars(strip_tags($this->isbn));
        $this->genre = htmlspecialchars(strip_tags($this->genre));
        $this->condition = htmlspecialchars(strip_tags($this->condition));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Bind parameters
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":author", $this->author);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":genre", $this->genre);
        $stmt->bindParam(":condition", $this->condition);
        $stmt->bindParam(":description", $this->description);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    public function readByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function update() {
    // FIXED: Added backticks around 'condition' (reserved keyword)
    $query = "UPDATE " . $this->table_name . "
            SET title=:title, author=:author, 
            isbn=:isbn, genre=:genre, `condition`=:condition, 
            description=:description, status=:status
            WHERE id = :id AND user_id = :user_id";
    
    $stmt = $this->conn->prepare($query);
    
    // Sanitize inputs
    $this->title = htmlspecialchars(strip_tags($this->title));
    $this->author = htmlspecialchars(strip_tags($this->author));
    $this->isbn = htmlspecialchars(strip_tags($this->isbn));
    $this->genre = htmlspecialchars(strip_tags($this->genre));
    $this->condition = htmlspecialchars(strip_tags($this->condition));
    $this->description = htmlspecialchars(strip_tags($this->description));
    $this->status = htmlspecialchars(strip_tags($this->status));
    
    // Bind parameters
    $stmt->bindParam(":title", $this->title);
    $stmt->bindParam(":author", $this->author);
    $stmt->bindParam(":isbn", $this->isbn);
    $stmt->bindParam(":genre", $this->genre);
    $stmt->bindParam(":condition", $this->condition);
    $stmt->bindParam(":description", $this->description);
    $stmt->bindParam(":status", $this->status);
    $stmt->bindParam(":id", $this->id);
    $stmt->bindParam(":user_id", $this->user_id);
    
    return $stmt->execute();
}

public function readOne() {
    $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? AND user_id = ? LIMIT 0,1";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $this->id);
    $stmt->bindParam(2, $this->user_id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $this->title = $row['title'];
        $this->author = $row['author'];
        $this->isbn = $row['isbn'];
        $this->genre = $row['genre'];
        $this->condition = $row['condition'];
        $this->description = $row['description'];
        $this->status = $row['status'];
        return true;
    }
    
    return false;
}

public function delete() {
    $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id);
    $stmt->bindParam(":user_id", $this->user_id);
    
    return $stmt->execute();
}

// In your Book.php file, make sure this method exists:
public function search($search_term, $genre = '', $condition = '') {
    $query = "SELECT b.*, u.name as owner_name, u.city as location 
              FROM books b 
              JOIN users u ON b.user_id = u.id 
              WHERE b.status = 'Available'";
    
    $params = array();
    
    if (!empty($search_term)) {
        $query .= " AND (b.title LIKE :search OR b.author LIKE :search)";
        $params[':search'] = "%$search_term%";
    }
    
    if (!empty($genre)) {
        $query .= " AND b.genre = :genre";
        $params[':genre'] = $genre;
    }
    
    if (!empty($condition)) {
        $query .= " AND b.condition = :condition";
        $params[':condition'] = $condition;
    }
    
    $query .= " ORDER BY b.created_at DESC";
    
    $stmt = $this->conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt;
}

// Get book by ID
public function getBookById() {
    $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $this->id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $this->title = $row['title'];
        $this->author = $row['author'];
        $this->user_id = $row['user_id']; // Store the owner's user_id
        $this->status = $row['status'];
        return true;
    }
    
    return false;
}
}
?>