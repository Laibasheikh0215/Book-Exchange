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
    public $image_path2;
    public $image_path3;
    public $location;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE METHOD
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET user_id=:user_id, title=:title, author=:author, 
                isbn=:isbn, genre=:genre, `condition`=:condition, 
                description=:description, status='Available', 
                image_path=:image_path, image_path2=:image_path2, image_path3=:image_path3";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->author = htmlspecialchars(strip_tags($this->author));
        $this->isbn = htmlspecialchars(strip_tags($this->isbn));
        $this->genre = htmlspecialchars(strip_tags($this->genre));
        $this->condition = htmlspecialchars(strip_tags($this->condition));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->image_path = htmlspecialchars(strip_tags($this->image_path));
        $this->image_path2 = htmlspecialchars(strip_tags($this->image_path2));
        $this->image_path3 = htmlspecialchars(strip_tags($this->image_path3));
        
        // Bind parameters
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":author", $this->author);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":genre", $this->genre);
        $stmt->bindParam(":condition", $this->condition);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":image_path", $this->image_path);
        $stmt->bindParam(":image_path2", $this->image_path2);
        $stmt->bindParam(":image_path3", $this->image_path3);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // READ ALL METHOD 
public function readAll() {
    $query = "SELECT b.* FROM " . $this->table_name . " b 
              WHERE b.status = 'Available' 
              ORDER BY b.created_at DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    
    return $stmt;
}


    // SEARCH METHOD
        public function search($search_term) {
        $query = "SELECT 
                    b.*
                FROM " . $this->table_name . " b
                WHERE b.status = 'Available' 
                AND (b.title LIKE :search_term 
                     OR b.author LIKE :search_term 
                     OR b.genre LIKE :search_term)
                ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%{$search_term}%";
        $stmt->bindParam(":search_term", $search_term);
        $stmt->execute();
        
        return $stmt;
    }
}
?>