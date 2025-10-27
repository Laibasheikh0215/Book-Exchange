<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["message" => "User ID is required"]);
    exit();
}

try {
    $notification = new Notification($db);
    $stmt = $notification->getByUser($user_id, $limit);
    $notifications = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notifications[] = $row;
    }

    echo json_encode($notifications);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server error"]);
}
?>