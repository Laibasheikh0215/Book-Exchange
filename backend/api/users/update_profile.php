<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (!empty($data->user_id)) {
        $query = "UPDATE users SET 
                  name = :name, 
                  email = :email, 
                  city = :city, 
                  address = :address, 
                  bio = :bio,
                  profile_picture = :profile_picture,
                  updated_at = NOW()
                  WHERE id = :user_id";

        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":user_id", $data->user_id);
        $stmt->bindParam(":name", $data->name);
        $stmt->bindParam(":email", $data->email);
        $stmt->bindParam(":city", $data->city);
        $stmt->bindParam(":address", $data->address);
        $stmt->bindParam(":bio", $data->bio);
        $stmt->bindParam(":profile_picture", $data->profile_picture);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update profile"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "User ID required"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>