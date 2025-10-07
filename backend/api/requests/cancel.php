<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/Request.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized."));
        exit();
    }

    if (empty($data->request_id)) {
        http_response_code(400);
        echo json_encode(array("message" => "Request ID is required."));
        exit();
    }

    $request = new BookRequest($db);
    $request->id = $data->request_id;
    $request->requester_id = $_SESSION['user_id'];

    if ($request->cancel()) {
        echo json_encode(array("message" => "Request cancelled successfully."));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Unable to cancel request."));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error: " . $e->getMessage()));
}
?>