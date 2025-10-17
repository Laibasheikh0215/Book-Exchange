<?php
class Request {
    private $conn;
    private $table_name = "book_requests";

    public $id;
    public $book_id;
    public $requester_id;
    public $owner_id;
    public $status;
    public $request_type;
    public $message;
    public $proposed_return_date;
    public $actual_return_date;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new request
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET book_id=:book_id, requester_id=:requester_id, owner_id=:owner_id, 
                request_type=:request_type, message=:message, proposed_return_date=:proposed_return_date";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":requester_id", $this->requester_id);
        $stmt->bindParam(":owner_id", $this->owner_id);
        $stmt->bindParam(":request_type", $this->request_type);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":proposed_return_date", $this->proposed_return_date);
        
        return $stmt->execute();
    }

    // Count pending requests for owner
    public function countPendingRequests($owner_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE owner_id = :owner_id AND status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":owner_id", $owner_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Get recent requests for dashboard
    public function getRecentRequests($owner_id, $limit = 5) {
        $query = "SELECT r.*, b.title as book_title, u.name as requester_name 
                  FROM " . $this->table_name . " r
                  JOIN books b ON r.book_id = b.id
                  JOIN users u ON r.requester_id = u.id
                  WHERE r.owner_id = :owner_id
                  ORDER BY r.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":owner_id", $owner_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get requests by owner (incoming requests)
    public function getByOwner($owner_id) {
        $query = "SELECT r.*, b.title as book_title, b.author as book_author,
                         b.image_url as book_image, u.name as requester_name
                  FROM " . $this->table_name . " r
                  JOIN books b ON r.book_id = b.id
                  JOIN users u ON r.requester_id = u.id
                  WHERE r.owner_id = :owner_id
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":owner_id", $owner_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Update request status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . "
                SET status=:status, updated_at=CURRENT_TIMESTAMP
                WHERE id=:id AND owner_id=:owner_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":owner_id", $this->owner_id);
        
        return $stmt->execute();
    }
}
?>