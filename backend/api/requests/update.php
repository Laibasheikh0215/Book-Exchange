<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Request.php';

$database = new Database();
$db = $database->getConnection();

$request = new Request($db);

// ✅ DEBUG: Log incoming data
$input = file_get_contents("php://input");
error_log("📥 Received data: " . $input);
$data = json_decode($input);

// ✅ DEBUG: Log parsed data
error_log("📥 Parsed data: " . print_r($data, true));

if(
    !empty($data->request_id) &&
    !empty($data->status) &&
    !empty($data->user_id)
) {
    error_log("✅ Data validation passed");
    
    $request->id = $data->request_id;
    
    // First get the request to verify ownership
    $current_request = $request->getRequestById();
    
    if(!$current_request) {
        http_response_code(404);
        echo json_encode(array(
            "success" => false,
            "message" => "Request not found."
        ));
        error_log("❌ Request not found: " . $data->request_id);
        return;
    }
    
    error_log("✅ Request found: " . print_r($current_request, true));
    
    // Check if user has permission to update this request
    $can_update = false;
    $new_status = ucfirst($data->status);
    
    // ✅ FIX: Allow both 'Approved' and 'Accepted' status for owners
    if($current_request['owner_id'] == $data->user_id) {
        // Owner can accept/reject incoming requests
        if(in_array($new_status, ['Approved', 'Accepted', 'Rejected'])) {
            $can_update = true;
            // Normalize status to 'Approved'
            if($new_status == 'Accepted') {
                $new_status = 'Approved';
            }
        }
        error_log("👤 User is owner, can_update: " . ($can_update ? 'YES' : 'NO'));
    }
    
    if($current_request['requester_id'] == $data->user_id) {
        // Requester can cancel their own requests
        if(in_array($new_status, ['Cancelled', 'Canceled'])) {
            $can_update = true;
            // Normalize status to 'Cancelled'
            if($new_status == 'Canceled') {
                $new_status = 'Cancelled';
            }
        }
        error_log("👤 User is requester, can_update: " . ($can_update ? 'YES' : 'NO'));
    }
    
    if(!$can_update) {
        http_response_code(403);
        echo json_encode(array(
            "success" => false,
            "message" => "You don't have permission to update this request.",
            "debug" => [
                "user_id" => $data->user_id,
                "owner_id" => $current_request['owner_id'],
                "requester_id" => $current_request['requester_id'],
                "requested_status" => $new_status
            ]
        ));
        error_log("❌ Permission denied for user: " . $data->user_id);
        return;
    }
    
    $request->status = $new_status;
    
    if($request->update()) {
        error_log("✅ Database update successful");
        
        // If request is approved, update book status
        if($new_status == 'Approved') {
            $request->book_id = $current_request['book_id'];
            $request->updateBookStatus('Reserved');
        }
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Request {$new_status} successfully."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "success" => false,
            "message" => "Unable to update request in database."
        ));
        error_log("❌ Database update failed");
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Unable to update request. Data is incomplete.",
        "debug_received" => $data,
        "required_fields" => [
            "request_id" => isset($data->request_id) ? $data->request_id : "MISSING",
            "status" => isset($data->status) ? $data->status : "MISSING", 
            "user_id" => isset($data->user_id) ? $data->user_id : "MISSING"
        ]
    ));
    error_log("❌ Incomplete data received");
}
?>