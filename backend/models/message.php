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

    public function send() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET sender_id=:sender_id, receiver_id=:receiver_id, message_text=:message_text";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":message_text", $this->message_text);

        if($stmt->execute()) {
            $this->updateConversation($this->sender_id, $this->receiver_id, $this->message_text);
            return true;
        }
        return false;
    }

    private function updateConversation($user1_id, $user2_id, $last_message) {
        $min_id = min($user1_id, $user2_id);
        $max_id = max($user1_id, $user2_id);

        $query = "INSERT INTO conversations (user1_id, user2_id, last_message, last_message_at) 
                  VALUES (:user1_id, :user2_id, :last_message, NOW())
                  ON DUPLICATE KEY UPDATE 
                  last_message = :last_message, last_message_at = NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user1_id", $min_id);
        $stmt->bindParam(":user2_id", $max_id);
        $stmt->bindParam(":last_message", $last_message);
        $stmt->execute();
    }

    public function getConversations($user_id) {
        $query = "SELECT 
                    CASE 
                        WHEN c.user1_id = :user_id THEN c.user2_id 
                        ELSE c.user1_id 
                    END as other_user_id,
                    u.name as other_user_name,
                    u.profile_picture as other_user_avatar,
                    c.last_message,
                    c.last_message_at,
                    (SELECT COUNT(*) FROM messages m 
                     WHERE ((m.sender_id = other_user_id AND m.receiver_id = :user_id2) 
                            OR (m.sender_id = :user_id3 AND m.receiver_id = other_user_id))
                     AND m.is_read = 0 AND m.receiver_id = :user_id4) as unread_count
                  FROM conversations c
                  JOIN users u ON u.id = CASE 
                      WHEN c.user1_id = :user_id5 THEN c.user2_id 
                      ELSE c.user1_id 
                  END
                  WHERE c.user1_id = :user_id6 OR c.user2_id = :user_id7
                  ORDER BY c.last_message_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":user_id2", $user_id);
        $stmt->bindParam(":user_id3", $user_id);
        $stmt->bindParam(":user_id4", $user_id);
        $stmt->bindParam(":user_id5", $user_id);
        $stmt->bindParam(":user_id6", $user_id);
        $stmt->bindParam(":user_id7", $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function getMessages($user1_id, $user2_id) {
        $query = "SELECT m.*, u.name as sender_name 
                  FROM messages m 
                  JOIN users u ON m.sender_id = u.id 
                  WHERE (m.sender_id = :user1_id AND m.receiver_id = :user2_id) 
                     OR (m.sender_id = :user2_id2 AND m.receiver_id = :user1_id2) 
                  ORDER BY m.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user1_id", $user1_id);
        $stmt->bindParam(":user2_id", $user2_id);
        $stmt->bindParam(":user2_id2", $user2_id);
        $stmt->bindParam(":user1_id2", $user1_id);
        $stmt->execute();

        return $stmt;
    }

    public function markAsRead($user_id, $other_user_id) {
        $query = "UPDATE messages 
                  SET is_read = 1 
                  WHERE sender_id = :other_user_id AND receiver_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":other_user_id", $other_user_id);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }
}
?>