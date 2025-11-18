<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$request = new Request($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

$pending_count = $request->getPendingRequestsCount($user_id);

echo json_encode(array(
    "count" => $pending_count
));
?>