<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

http_response_code(200);
echo json_encode(array("message" => "Logged out successfully."));
?>