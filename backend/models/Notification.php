<?php
class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $is_read;
    public $related_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new notification
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET user_id=:user_id, title=:title, message=:message, 
                     type=:type, related_id=:related_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->type = htmlspecialchars(strip_tags($this->type));

        // Bind data
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":related_id", $this->related_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get user notifications
    public function getUserNotifications($user_id, $limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Mark single notification as read
    public function markAsRead() {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_read = 1 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Mark all notifications as read for user
    public function markAllAsRead() {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_read = 1 
                 WHERE user_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get unread count
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                 WHERE user_id = ? AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
}
?>