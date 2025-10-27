<?php
class Message {
    private $conn;
    private $table_name = "messages";

    public $id;
    public $sender_id;
    public $receiver_id;
    public $message_text;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Send new message
    public function send() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (sender_id, receiver_id, message_text) 
                     VALUES (:sender_id, :receiver_id, :message_text)";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->sender_id = htmlspecialchars(strip_tags($this->sender_id));
            $this->receiver_id = htmlspecialchars(strip_tags($this->receiver_id));
            $this->message_text = htmlspecialchars(strip_tags($this->message_text));

            // Bind parameters
            $stmt->bindParam(":sender_id", $this->sender_id);
            $stmt->bindParam(":receiver_id", $this->receiver_id);
            $stmt->bindParam(":message_text", $this->message_text);

            if ($stmt->execute()) {
                return true;
            }
            return false;
            
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Get messages between two users
    public function getMessages($user1_id, $user2_id) {
        $query = "SELECT m.*, u.name as sender_name
                  FROM " . $this->table_name . " m
                  LEFT JOIN users u ON m.sender_id = u.id
                  WHERE (m.sender_id = :user1_id AND m.receiver_id = :user2_id)
                  OR (m.sender_id = :user2_id AND m.receiver_id = :user1_id)
                  ORDER BY m.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user1_id", $user1_id);
        $stmt->bindParam(":user2_id", $user2_id);
        $stmt->execute();

        return $stmt;
    }

    // Mark messages as read
    public function markAsRead($sender_id, $receiver_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE sender_id = :sender_id AND receiver_id = :receiver_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sender_id", $sender_id);
        $stmt->bindParam(":receiver_id", $receiver_id);

        return $stmt->execute();
    }

    // Count unread messages for user
    public function countUnreadMessages($user_id) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE receiver_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
}
?>