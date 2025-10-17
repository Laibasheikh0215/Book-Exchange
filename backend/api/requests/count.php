<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$request = new Request($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if($user_id) {
    $count = $request->countPendingRequests($user_id);
    echo json_encode(array("count" => $count));
} else {
    echo json_encode(array("count" => 0));
}
?>