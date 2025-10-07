<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

echo json_encode([
    "success" => true,
    "message" => "Test API is working",
    "timestamp" => date('Y-m-d H:i:s')
]);
?>