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
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new request
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET book_id=:book_id, requester_id=:requester_id, 
                owner_id=:owner_id, status=:status, request_type=:request_type,
                message=:message, proposed_return_date=:proposed_return_date";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->book_id = htmlspecialchars(strip_tags($this->book_id));
        $this->requester_id = htmlspecialchars(strip_tags($this->requester_id));
        $this->owner_id = htmlspecialchars(strip_tags($this->owner_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->request_type = htmlspecialchars(strip_tags($this->request_type));
        $this->message = htmlspecialchars(strip_tags($this->message));

        // Bind parameters
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":requester_id", $this->requester_id);
        $stmt->bindParam(":owner_id", $this->owner_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":request_type", $this->request_type);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":proposed_return_date", $this->proposed_return_date);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get requests by requester (outgoing)
    public function getByRequester($user_id) {
        $query = "SELECT r.*, b.title as book_title, b.author as book_author, 
                         b.image_path as book_image, u.name as owner_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN books b ON r.book_id = b.id
                  LEFT JOIN users u ON r.owner_id = u.id
                  WHERE r.requester_id = :user_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get requests by owner (incoming)
    public function getByOwner($user_id) {
        $query = "SELECT r.*, b.title as book_title, b.author as book_author, 
                         b.image_path as book_image, u.name as requester_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN books b ON r.book_id = b.id
                  LEFT JOIN users u ON r.requester_id = u.id
                  WHERE r.owner_id = :user_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Update request status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND owner_id = :owner_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":owner_id", $this->owner_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Cancel request
    // ✅ CANCEL REQUEST FUNCTION - IMPROVED VERSION
public function cancel() {
    $query = "UPDATE " . $this->table_name . "
            SET status = 'Cancelled', updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND requester_id = :requester_id AND status = 'Pending'";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":id", $this->id);
    $stmt->bindParam(":requester_id", $this->requester_id);

    if($stmt->execute() && $stmt->rowCount() > 0) {
        return true;
    }
    return false;
}

    // Count pending requests for a user
    public function countPendingRequests($user_id) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE owner_id = :user_id AND status = 'Pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Check if request exists and user has permission
    public function checkPermission($request_id, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND (requester_id = :user_id OR owner_id = :user_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $request_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>