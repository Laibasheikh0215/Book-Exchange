<?php
// Debug books data
include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Debug Books Data</h2>";

// Check if books table exists
$stmt = $db->query("SHOW TABLES LIKE 'books'");
if ($stmt->rowCount() > 0) {
    echo "✅ Books table exists<br>";
    
    // Count total books
    $stmt = $db->query("SELECT COUNT(*) as total FROM books");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total books in database: " . $result['total'] . "<br><br>";
    
    // Show all books with details
    $stmt = $db->query("SELECT 
        b.id, 
        b.title, 
        b.author, 
        b.status,
        u.id as user_id,
        u.name as user_name
    FROM books b 
    LEFT JOIN users u ON b.user_id = u.id 
    ORDER BY b.created_at DESC");
    
    echo "<h3>All Books Details:</h3>";
    $books_found = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books_found = true;
        echo "📚 Book ID: " . $row['id'] . "<br>";
        echo "Title: " . $row['title'] . "<br>";
        echo "Author: " . $row['author'] . "<br>"; 
        echo "Status: " . $row['status'] . "<br>";
        echo "User: " . $row['user_name'] . " (ID: " . $row['user_id'] . ")<br>";
        echo "---<br>";
    }
    
    if (!$books_found) {
        echo "❌ No books found in database!<br>";
    }
    
} else {
    echo "❌ Books table does NOT exist!<br>";
}

// Check users table
echo "<h3>Users Info:</h3>";
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total users: " . $result['total'] . "<br>";

// Show users with book counts
$stmt = $db->query("SELECT 
    u.id, 
    u.name, 
    u.email,
    COUNT(b.id) as book_count
FROM users u 
LEFT JOIN books b ON u.id = b.user_id 
GROUP BY u.id");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User: " . $row['name'] . " - Books: " . $row['book_count'] . "<br>";
}
?>