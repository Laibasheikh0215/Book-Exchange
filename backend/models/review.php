<?php
class Review {
    private $conn;
    private $table_name = "book_reviews";

    public $id;
    public $book_id;
    public $user_id;
    public $rating;
    public $comment;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new review
  // In Review.php - add this method to handle errors better
public function create() {
    try {
        // First check if user already reviewed this book
        $check_query = "SELECT id FROM " . $this->table_name . " 
                       WHERE user_id = :user_id AND book_id = :book_id";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":user_id", $this->user_id);
        $check_stmt->bindParam(":book_id", $this->book_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // Update existing review
            return $this->update();
        }

        // Create new review
        $query = "INSERT INTO " . $this->table_name . " 
                 SET book_id = :book_id, 
                     user_id = :user_id, 
                     rating = :rating, 
                     comment = :comment,
                     created_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->book_id = htmlspecialchars(strip_tags($this->book_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        // Bind parameters
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);

        if ($stmt->execute()) {
            return true;
        }
        
        error_log("Review creation failed: " . implode(", ", $stmt->errorInfo()));
        return false;
        
    } catch (PDOException $e) {
        error_log("Review creation error: " . $e->getMessage());
        return false;
    }
}

    // Update existing review
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET rating = :rating, 
                     comment = :comment,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id AND book_id = :book_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        // Bind parameters
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":book_id", $this->book_id);

        return $stmt->execute();
    }

    // Get reviews for a specific book
    public function getReviewsByBook($book_id) {
        $query = "SELECT r.*, u.name as user_name, u.profile_picture 
                  FROM " . $this->table_name . " r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.book_id = :book_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":book_id", $book_id);
        $stmt->execute();

        return $stmt;
    }

    // Get average rating for a book
    public function getAverageRating($book_id) {
        $query = "SELECT 
                    AVG(rating) as average_rating,
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
                  FROM " . $this->table_name . " 
                  WHERE book_id = :book_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":book_id", $book_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check if user has already reviewed a book
    public function getUserReview($user_id, $book_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
  // In Review.php - add this method to handle errors better
public function create() {
    try {
        // First check if user already reviewed this book
        $check_query = "SELECT id FROM " . $this->table_name . " 
                       WHERE user_id = :user_id AND book_id = :book_id";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":user_id", $this->user_id);
        $check_stmt->bindParam(":book_id", $this->book_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // Update existing review
            return $this->update();
        }

        // Create new review
        $query = "INSERT INTO " . $this->table_name . " 
                 SET book_id = :book_id, 
                     user_id = :user_id, 
                     rating = :rating, 
                     comment = :comment,
                     created_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->book_id = htmlspecialchars(strip_tags($this->book_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        // Bind parameters
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);

        if ($stmt->execute()) {
            return true;
        }
        
        error_log("Review creation failed: " . implode(", ", $stmt->errorInfo()));
        return false;
        
    } catch (PDOException $e) {
        error_log("Review creation error: " . $e->getMessage());
        return false;
    }
}                WHERE user_id = :user_id AND book_id = :book_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":book_id", $book_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>