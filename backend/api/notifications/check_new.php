<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_GET['user_id'] ?? 0;
$last_check = $_GET['last_check'] ?? '';

try {
    $response = [
        'success' => true,
        'has_new' => false,
        'notifications' => [],
        'counts' => [
            'unread_notifications' => 0,
            'pending_requests' => 0,
            'unread_messages' => 0
        ]
    ];

    // Get unread notifications count
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['counts']['unread_notifications'] = $result['count'];

    // Get pending requests count
    $query = "SELECT COUNT(*) as count FROM book_requests WHERE owner_id = ? AND status = 'Pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['counts']['pending_requests'] = $result['count'];

    // Get unread messages count
    $query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['counts']['unread_messages'] = $result['count'];

    // Check for new notifications since last check
    if (!empty($last_check)) {
        $query = "SELECT * FROM notifications 
                 WHERE user_id = ? AND created_at > ? AND is_read = 0 
                 ORDER BY created_at DESC 
                 LIMIT 10";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $last_check]);
        
        $new_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($new_notifications) > 0) {
            $response['has_new'] = true;
            $response['notifications'] = $new_notifications;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>