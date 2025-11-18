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

    // In the create() method of Request model, make sure it matches:
  public function create() {
    $query = "INSERT INTO " . $this->table_name . "
            SET
                book_id = :book_id,
                requester_id = :requester_id,
                owner_id = :owner_id,
                status = :status,
                request_type = :request_type,
                message = :message,
                proposed_return_date = :proposed_return_date";

    $stmt = $this->conn->prepare($query);

    // sanitize
    $this->book_id = htmlspecialchars(strip_tags($this->book_id));
    $this->requester_id = htmlspecialchars(strip_tags($this->requester_id));
    $this->owner_id = htmlspecialchars(strip_tags($this->owner_id));
    $this->status = htmlspecialchars(strip_tags($this->status));
    $this->request_type = htmlspecialchars(strip_tags($this->request_type));
    $this->message = htmlspecialchars(strip_tags($this->message));

    // bind values
    $stmt->bindParam(":book_id", $this->book_id);
    $stmt->bindParam(":requester_id", $this->requester_id);
    $stmt->bindParam(":owner_id", $this->owner_id);
    $stmt->bindParam(":status", $this->status);
    $stmt->bindParam(":request_type", $this->request_type);
    $stmt->bindParam(":message", $this->message);
    $stmt->bindParam(":proposed_return_date", $this->proposed_return_date);

    // ✅ RETURN SIMPLE BOOLEAN - NO EXTRA OUTPUT
    if($stmt->execute()) {
        return true;
    }
    return false;
}  

    public function getIncomingRequests($user_id) {
        $query = "SELECT 
                    br.id, 
                    br.book_id,
                    b.title as book_title,
                    br.requester_id,
                    u1.name as requester_name,
                    br.owner_id,
                    u2.name as owner_name,
                    br.status,
                    br.request_type,
                    br.message,
                    br.proposed_return_date,
                    br.actual_return_date,
                    br.created_at
                FROM " . $this->table_name . " br
                LEFT JOIN books b ON br.book_id = b.id
                LEFT JOIN users u1 ON br.requester_id = u1.id
                LEFT JOIN users u2 ON br.owner_id = u2.id
                WHERE br.owner_id = ?
                ORDER BY br.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function getOutgoingRequests($user_id) {
        $query = "SELECT 
                    br.id, 
                    br.book_id,
                    b.title as book_title,
                    br.requester_id,
                    u1.name as requester_name,
                    br.owner_id,
                    u2.name as owner_name,
                    br.status,
                    br.request_type,
                    br.message,
                    br.proposed_return_date,
                    br.actual_return_date,
                    br.created_at
                FROM " . $this->table_name . " br
                LEFT JOIN books b ON br.book_id = b.id
                LEFT JOIN users u1 ON br.requester_id = u1.id
                LEFT JOIN users u2 ON br.owner_id = u2.id
                WHERE br.requester_id = ?
                ORDER BY br.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function getRequestById() {
        $query = "SELECT 
                    br.id, 
                    br.book_id,
                    b.title as book_title,
                    br.requester_id,
                    u1.name as requester_name,
                    br.owner_id,
                    u2.name as owner_name,
                    br.status,
                    br.request_type,
                    br.message,
                    br.proposed_return_date,
                    br.actual_return_date,
                    br.created_at
                FROM " . $this->table_name . " br
                LEFT JOIN books b ON br.book_id = b.id
                LEFT JOIN users u1 ON br.requester_id = u1.id
                LEFT JOIN users u2 ON br.owner_id = u2.id
                WHERE br.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateBookStatus($status) {
        $query = "UPDATE books 
                SET status = :status 
                WHERE id = :book_id";

        $stmt = $this->conn->prepare($query);

        $status = htmlspecialchars(strip_tags($status));
        $book_id = htmlspecialchars(strip_tags($this->book_id));

        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":book_id", $book_id);

        return $stmt->execute();
    }

    public function getPendingRequestsCount($user_id) {
        $query = "SELECT COUNT(*) as count 
                FROM " . $this->table_name . " 
                WHERE owner_id = ? AND status = 'Pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
}
?>