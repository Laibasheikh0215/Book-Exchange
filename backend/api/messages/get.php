<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : die();

try {
    $query = "SELECT id, name, email, profile_picture FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($user);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "User not found."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving user: " . $e->getMessage()));
}
?>