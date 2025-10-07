<?php
class BookRequest {
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
                SET book_id=:book_id, requester_id=:requester_id, 
                owner_id=:owner_id, request_type=:request_type, 
                message=:message, proposed_return_date=:proposed_return_date";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->book_id = htmlspecialchars(strip_tags($this->book_id));
        $this->requester_id = htmlspecialchars(strip_tags($this->requester_id));
        $this->owner_id = htmlspecialchars(strip_tags($this->owner_id));
        $this->request_type = htmlspecialchars(strip_tags($this->request_type));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->proposed_return_date = htmlspecialchars(strip_tags($this->proposed_return_date));
        
        // Bind parameters
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":requester_id", $this->requester_id);
        $stmt->bindParam(":owner_id", $this->owner_id);
        $stmt->bindParam(":request_type", $this->request_type);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":proposed_return_date", $this->proposed_return_date);
        
        return $stmt->execute();
    }

    // Get requests by requester (outgoing requests)
    public function getByRequester($requester_id) {
        $query = "SELECT r.*, b.title as book_title, b.author as book_author, 
                         b.image_path as book_image, u.name as owner_name
                  FROM " . $this->table_name . " r
                  JOIN books b ON r.book_id = b.id
                  JOIN users u ON r.owner_id = u.id
                  WHERE r.requester_id = ?
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $requester_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Get requests by owner (incoming requests)
    public function getByOwner($owner_id) {
        $query = "SELECT r.*, b.title as book_title, b.author as book_author,
                         b.image_path as book_image, u.name as requester_name
                  FROM " . $this->table_name . " r
                  JOIN books b ON r.book_id = b.id
                  JOIN users u ON r.requester_id = u.id
                  WHERE r.owner_id = ?
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $owner_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Update request status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . "
                SET status=:status, updated_at=CURRENT_TIMESTAMP
                WHERE id=:id AND owner_id=:owner_id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":owner_id", $this->owner_id);
        
        return $stmt->execute();
    }

    // Cancel request (by requester)
    public function cancel() {
        $query = "UPDATE " . $this->table_name . "
                SET status='Rejected', updated_at=CURRENT_TIMESTAMP
                WHERE id=:id AND requester_id=:requester_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":requester_id", $this->requester_id);
        
        return $stmt->execute();
    }

    // Get single request by ID
    public function getById($request_id) {
        $query = "SELECT r.*, b.title as book_title, b.author as book_author,
                         u1.name as requester_name, u2.name as owner_name
                  FROM " . $this->table_name . " r
                  JOIN books b ON r.book_id = b.id
                  JOIN users u1 ON r.requester_id = u1.id
                  JOIN users u2 ON r.owner_id = u2.id
                  WHERE r.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $request_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>