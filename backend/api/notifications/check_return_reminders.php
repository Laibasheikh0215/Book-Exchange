<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Find approved borrow requests with upcoming return dates (within 3 days)
    $query = "SELECT br.id, br.requester_id, br.owner_id, br.proposed_return_date, 
                     b.title, u1.name as requester_name, u2.name as owner_name
              FROM book_requests br
              JOIN books b ON br.book_id = b.id
              JOIN users u1 ON br.requester_id = u1.id
              JOIN users u2 ON br.owner_id = u2.id
              WHERE br.status = 'Approved' 
              AND br.request_type = 'Borrow'
              AND br.proposed_return_date IS NOT NULL
              AND DATE(br.proposed_return_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
              AND NOT EXISTS (
                  SELECT 1 FROM notifications n 
                  WHERE n.related_id = br.id 
                  AND n.type = 'Reminder'
                  AND DATE(n.created_at) = CURDATE()
              )";

    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $reminders_created = 0;
    $notification = new Notification($db);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $return_date = new DateTime($row['proposed_return_date']);
        $today = new DateTime();
        $days_remaining = $today->diff($return_date)->days;
        
        if ($days_remaining >= 0) {
            // Create reminder for book owner
            $owner_message = "📚 Return Reminder: '{$row['title']}' is due in {$days_remaining} day(s). Borrower: {$row['requester_name']}";
            
            $notification->user_id = $row['owner_id'];
            $notification->title = "⏰ Book Return Reminder";
            $notification->message = $owner_message;
            $notification->type = "Reminder";
            $notification->related_id = $row['id'];
            
            if ($notification->create()) {
                $reminders_created++;
                echo "Created owner reminder for request {$row['id']}\n";
            }

            // Create reminder for borrower
            $borrower_message = "📚 Return Due: '{$row['title']}' is due in {$days_remaining} day(s). Please return to: {$row['owner_name']}";
            
            $notification->user_id = $row['requester_id'];
            $notification->title = "⏰ Book Return Due";
            $notification->message = $borrower_message;
            $notification->type = "Reminder";
            $notification->related_id = $row['id'];
            
            if ($notification->create()) {
                $reminders_created++;
                echo "Created borrower reminder for request {$row['id']}\n";
            }
        }
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Return reminders checked successfully.",
        "reminders_created" => $reminders_created,
        "checked_requests" => $stmt->rowCount()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "reminders_created" => 0
    ]);
}
?>