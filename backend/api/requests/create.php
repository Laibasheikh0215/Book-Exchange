<?php
header("Access-Control-Allow-Origin: http://localhost");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';
include_once '../../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$input = file_get_contents("php://input");
$data = json_decode($input);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data received"
    ]);
    exit();
}

if (empty($data->book_id) || empty($data->user_id) || empty($data->request_type)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Book ID, User ID and Request Type are required"
    ]);
    exit();
}

try {
    $request = new Request($db);
    
    $request->book_id = $data->book_id;
    $request->requester_id = $data->user_id;
    $request->request_type = $data->request_type;
    $request->message = $data->message ?? '';
    $request->proposed_return_date = $data->proposed_return_date ?? null;
    
    $query = "SELECT user_id, title FROM books WHERE id = :book_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":book_id", $data->book_id);
    $stmt->execute();
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Book not found"
        ]);
        exit();
    }
    
    $request->owner_id = $book['user_id'];
    $request->status = 'Pending';
    
    if ($request->create()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Request created successfully",
            "request_id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to create request in database"
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>