<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$request = new Request($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();
$type = isset($_GET['type']) ? $_GET['type'] : 'incoming';

if($type == 'incoming') {
    $stmt = $request->getIncomingRequests($user_id);
} else {
    $stmt = $request->getOutgoingRequests($user_id);
}

$num = $stmt->rowCount();

if($num > 0) {
    $requests_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $request_item = array(
            "id" => $id,
            "book_id" => $book_id,
            "book_title" => $book_title,
            "requester_id" => $requester_id,
            "requester_name" => $requester_name,
            "owner_id" => $owner_id,
            "owner_name" => $owner_name,
            "status" => $status,
            "request_type" => $request_type,
            "message" => $message,
            "proposed_return_date" => $proposed_return_date,
            "actual_return_date" => $actual_return_date,
            "created_at" => $created_at
        );
        
        array_push($requests_arr, $request_item);
    }
    
    echo json_encode($requests_arr);
} else {
    echo json_encode(array());
}
?>