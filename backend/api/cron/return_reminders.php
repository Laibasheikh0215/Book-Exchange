<?php
include_once '../config/database.php';
include_once '../models/Notification.php';
include_once '../notifications/triggers.php';

$database = new Database();
$db = $database->getConnection();

// Get approved borrow requests with return date in next 2 days
$query = "SELECT br.*, b.title as book_title, u.name as borrower_name 
          FROM book_requests br
          JOIN books b ON br.book_id = b.id
          JOIN users u ON br.requester_id = u.id
          WHERE br.status = 'Approved' 
          AND br.request_type = 'Borrow'
          AND br.proposed_return_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
          AND br.return_reminder_sent = 0";

$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($requests as $request) {
    // Send reminder to borrower
    notifyReturnReminder(
        $db,
        $request['id'],
        $request['requester_id'],
        $request['book_title'],
        $request['proposed_return_date']
    );
    
    // Mark as reminder sent
    $updateQuery = "UPDATE book_requests SET return_reminder_sent = 1 WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$request['id']]);
    
    echo "Reminder sent for request ID: " . $request['id'] . "\n";
}

echo "Return reminders processed successfully!";
?>