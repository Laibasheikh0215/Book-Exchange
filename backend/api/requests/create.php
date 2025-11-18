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

$data = json_decode(file_get_contents("php://input"));

// ✅ DEBUG: Log received data
error_log("📥 Received request data: " . print_r($data, true));

// ✅ FIXED VALIDATION: Check for ALL required fields
if(
    !empty($data->book_id) &&
    !empty($data->requester_id) && 
    !empty($data->owner_id) &&
    !empty($data->request_type)
) {
    error_log("✅ All required fields present");
    
    $request->book_id = $data->book_id;
    $request->requester_id = $data->requester_id;
    $request->owner_id = $data->owner_id;
    $request->request_type = $data->request_type;
    $request->message = $data->message ?? '';
    $request->proposed_return_date = $data->proposed_return_date ?? null;
    $request->status = 'Pending';

    if($request->create()) {
        http_response_code(201);
        
        // ✅ FIX: ONLY ONE JSON RESPONSE - REMOVE DUPLICATE ECHO
        echo json_encode(array(
            "success" => true,
            "message" => "Book request created successfully.",
            "request_id" => $db->lastInsertId()
        ));
        
        error_log("✅ Request created successfully - SINGLE RESPONSE SENT");
        
    } else {
        http_response_code(503);
        echo json_encode(array(
            "success" => false,
            "message" => "Unable to create book request in database."
        ));
        error_log("❌ Database creation failed");
    }
} else {
    http_response_code(400);
    
    // ✅ BETTER ERROR REPORTING
    $missing_fields = [];
    if(empty($data->book_id)) $missing_fields[] = "book_id";
    if(empty($data->requester_id)) $missing_fields[] = "requester_id";
    if(empty($data->owner_id)) $missing_fields[] = "owner_id";
    if(empty($data->request_type)) $missing_fields[] = "request_type";
    
    echo json_encode(array(
        "success" => false,
        "message" => "Unable to create book request. Data is incomplete.",
        "missing_fields" => $missing_fields
    ));
    
    error_log("❌ Missing fields: " . implode(', ', $missing_fields));
}

// ✅ ADD THIS: EXIT AFTER SENDING RESPONSE TO PREVENT EXTRA OUTPUT
exit();
?>