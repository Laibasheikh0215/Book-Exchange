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
    public $price; 
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
    // In Book.php - Update create() method
public function create() {
    $query = "INSERT INTO " . $this->table_name . "
            SET user_id=:user_id, title=:title, author=:author, 
            isbn=:isbn, genre=:genre, `condition`=:condition, 
            price=:price, description=:description, status='Available', 
            image_path=:image_path, image_path2=:image_path2, image_path3=:image_path3";
    
    $stmt = $this->conn->prepare($query);
    
    // Sanitize inputs (add price)
    $this->title = htmlspecialchars(strip_tags($this->title));
    $this->author = htmlspecialchars(strip_tags($this->author));
    $this->isbn = htmlspecialchars(strip_tags($this->isbn));
    $this->genre = htmlspecialchars(strip_tags($this->genre));
    $this->condition = htmlspecialchars(strip_tags($this->condition));
    $this->price = htmlspecialchars(strip_tags($this->price));
    $this->description = htmlspecialchars(strip_tags($this->description));
    $this->image_path = htmlspecialchars(strip_tags($this->image_path));
    $this->image_path2 = htmlspecialchars(strip_tags($this->image_path2));
    $this->image_path3 = htmlspecialchars(strip_tags($this->image_path3));
    
    // Bind parameters (add price)
    $stmt->bindParam(":user_id", $this->user_id);
    $stmt->bindParam(":title", $this->title);
    $stmt->bindParam(":author", $this->author);
    $stmt->bindParam(":isbn", $this->isbn);
    $stmt->bindParam(":genre", $this->genre);
    $stmt->bindParam(":condition", $this->condition);
    $stmt->bindParam(":price", $this->price);
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

    // Add this method to your Book.php file, after the search() method:

// DELETE METHOD
public function delete() {
    try {
        // Log for debugging
        error_log("Deleting book ID: " . $this->id . " for user: " . $this->user_id);
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        if ($stmt->execute()) {
            // Check if any row was actually deleted
            $rowCount = $stmt->rowCount();
            error_log("Delete query executed. Rows affected: " . $rowCount);
            
            if ($rowCount > 0) {
                return true;
            } else {
                error_log("No book found with ID: " . $this->id . " for user: " . $this->user_id);
                return false;
            }
        } else {
            error_log("Delete query failed to execute");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("General delete error: " . $e->getMessage());
        return false;
    }
}
}
?>