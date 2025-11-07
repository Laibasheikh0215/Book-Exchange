<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection test
$host = "localhost";
$db_name = "book_exchange";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo json_encode(["message" => "✅ Database connected successfully"]);
    
    // Check if notifications table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'notifications'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo json_encode(["message" => "✅ Notifications table exists"]);
        
        // Check table structure
        $stmt = $conn->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["columns" => $columns]);
        
        // Check if any data exists
        $stmt = $conn->query("SELECT COUNT(*) as count FROM notifications");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["record_count" => $count['count']]);
        
    } else {
        echo json_encode(["message" => "❌ Notifications table does not exist"]);
        
        // Create table if it doesn't exist
        $createTable = "
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('Request', 'Message', 'System', 'Reminder') DEFAULT 'System',
            is_read BOOLEAN DEFAULT FALSE,
            related_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $conn->exec($createTable);
        echo json_encode(["message" => "✅ Notifications table created successfully"]);
    }
    
} catch(PDOException $e) {
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
}
?>