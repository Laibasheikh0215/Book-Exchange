<?php
class Message {
    private $conn;
    private $table_name = "messages";

    public $id;
    public $sender_id;
    public $receiver_id;
    public $message_text;
    public $book_id;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET sender_id=:sender_id, receiver_id=:receiver_id, 
                message_text=:message_text, book_id=:book_id, is_read=:is_read, created_at=:created_at";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":message_text", $this->message_text);
        $stmt->bindParam(":book_id", $this->book_id);
        $stmt->bindParam(":is_read", $this->is_read);
        $stmt->bindParam(":created_at", $this->created_at);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // ✅ FIXED: getConversations method with proper query
    public function getConversations($user_id) {
        $query = "SELECT 
                    DISTINCT u.id as other_user_id,
                    u.name as other_user_name,
                    u.email as other_user_email,
                    (SELECT message_text FROM messages 
                     WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages 
                     WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
                     ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages 
                     WHERE ((sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) 
                     AND is_read = 0 AND receiver_id = ?) as unread_count
                FROM users u
                WHERE u.id IN (
                    SELECT DISTINCT 
                        CASE 
                            WHEN sender_id = ? THEN receiver_id 
                            ELSE sender_id 
                        END as other_user
                    FROM messages 
                    WHERE sender_id = ? OR receiver_id = ?
                )
                ORDER BY last_message_time DESC";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters - user_id repeated for all placeholders
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $user_id);
        $stmt->bindParam(3, $user_id);
        $stmt->bindParam(4, $user_id);
        $stmt->bindParam(5, $user_id);
        $stmt->bindParam(6, $user_id);
        $stmt->bindParam(7, $user_id);
        $stmt->bindParam(8, $user_id);
        $stmt->bindParam(9, $user_id);
        $stmt->bindParam(10, $user_id);

        $stmt->execute();
        return $stmt;
    }

    // ✅ SIMPLIFIED VERSION - Alternative if above doesn't work
    public function getConversationsSimple($user_id) {
        $query = "SELECT 
                    u.id as other_user_id,
                    u.name as other_user_name,
                    u.email as other_user_email,
                    m.message_text as last_message,
                    m.created_at as last_message_time,
                    (SELECT COUNT(*) FROM messages 
                     WHERE ((sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) 
                     AND is_read = 0 AND receiver_id = ?) as unread_count
                FROM users u
                INNER JOIN messages m ON (
                    (m.sender_id = u.id AND m.receiver_id = ?) OR 
                    (m.sender_id = ? AND m.receiver_id = u.id)
                )
                WHERE u.id != ?
                GROUP BY u.id, u.name, u.email
                ORDER BY m.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $user_id);
        $stmt->bindParam(3, $user_id);
        $stmt->bindParam(4, $user_id);
        $stmt->bindParam(5, $user_id);
        $stmt->bindParam(6, $user_id);

        $stmt->execute();
        return $stmt;
    }

    public function getMessages($user_id, $other_user_id) {
        $query = "SELECT m.*, 
                    u1.name as sender_name,
                    u2.name as receiver_name
                FROM messages m
                LEFT JOIN users u1 ON m.sender_id = u1.id
                LEFT JOIN users u2 ON m.receiver_id = u2.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $other_user_id);
        $stmt->bindParam(3, $other_user_id);
        $stmt->bindParam(4, $user_id);

        $stmt->execute();
        return $stmt;
    }

    public function markAsRead($user_id, $other_user_id) {
        $query = "UPDATE " . $this->table_name . " 
                SET is_read = 1 
                WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $other_user_id);

        return $stmt->execute();
    }
}
?>