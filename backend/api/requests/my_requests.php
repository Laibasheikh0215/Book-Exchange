<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$request = new Request($db);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : 'outgoing'; // outgoing or incoming

if($user_id) {
    if($type === 'incoming') {
        $result = $request->getByOwner($user_id);
    } else {
        $result = $request->getByRequester($user_id);
    }

    $requests_arr = array();
    
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $request_item = array(
                "id" => $row['id'],
                "book_id" => $row['book_id'],
                "book_title" => $row['book_title'],
                "book_author" => $row['book_author'],
                "book_image" => $row['book_image'],
                "requester_name" => $row['requester_name'] ?? null,
                "owner_name" => $row['owner_name'] ?? null,
                "status" => $row['status'],
                "request_type" => $row['request_type'],
                "message" => $row['message'],
                "created_at" => $row['created_at']
            );
            array_push($requests_arr, $request_item);
        }
    }
    
    echo json_encode($requests_arr);
} else {
    echo json_encode(array());
}
?>