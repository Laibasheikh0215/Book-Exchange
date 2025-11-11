<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../objects/message.php';

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$query = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(array("count" => $row['count']));
?>