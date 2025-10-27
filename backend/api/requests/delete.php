<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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

    if (empty($data->request_id) || empty($data->user_id)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Request ID and User ID are required."
        ));
        exit();
    }

    $request = new Request($db);
    $request->id = $data->request_id;
    $request->requester_id = $data->user_id;

    // First check if user has permission
    if (!$request->checkPermission($data->request_id, $data->user_id)) {
        http_response_code(403);
        echo json_encode(array(
            "success" => false,
            "message" => "You don't have permission to delete this request."
        ));
        exit();
    }

    $query = "DELETE FROM book_requests WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $data->request_id);

    if ($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Request deleted successfully."
        ));
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Unable to delete request."
        ));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ));
}
?>