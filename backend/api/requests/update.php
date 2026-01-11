<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Request.php';
include_once '../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

$request = new Request($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->status)) {
    $request->id = $data->id;
    
    // Get current request details
    $current_request = $request->getRequestById($data->id);
    
    if (!$current_request) {
        http_response_code(404);
        echo json_encode(["message" => "Request not found", "success" => false]);
        exit();
    }
    
    // Update status
    $request->status = $data->status;
    
    if ($request->update()) {
        $response = [
            "success" => true,
            "message" => "Request updated successfully"
        ];
        
        // If status changed to 'Approved', prepare transaction data
        if ($data->status === 'Approved') {
            $response['needs_transaction'] = true;
            $response['transaction_data'] = [
                'request_id' => $data->id,
                'book_id' => $current_request['book_id'],
                'book_title' => $current_request['book_title'],
                'book_price' => $current_request['price'],
                'borrower_id' => $current_request['requester_id'],
                'borrower_name' => $current_request['requester_name'],
                'owner_id' => $current_request['owner_id'],
                'owner_name' => $current_request['owner_name']
            ];
            
            // Update book status to reserved
            $update_book = "UPDATE books SET status = 'Reserved' WHERE id = :book_id";
            $stmt = $db->prepare($update_book);
            $stmt->bindParam(":book_id", $current_request['book_id']);
            $stmt->execute();
        }
        
        // Send notification
        $notification = new Notification($db);
        
        if ($data->status === 'Approved') {
            $title = "Request Approved!";
            $message = "Your request for '{$current_request['book_title']}' has been approved by the owner.";
            
            // Notify borrower
            $notification->user_id = $current_request['requester_id'];
            $notification->title = $title;
            $notification->message = $message;
            $notification->type = "Request";
            $notification->related_id = $data->id;
            $notification->create();
            
            $response['message'] .= " Transaction can now be completed.";
            
        } elseif ($data->status === 'Rejected') {
            $title = "Request Declined";
            $message = "Your request for '{$current_request['book_title']}' has been declined by the owner.";
            
            // Notify borrower
            $notification->user_id = $current_request['requester_id'];
            $notification->title = $title;
            $notification->message = $message;
            $notification->type = "Request";
            $notification->related_id = $data->id;
            $notification->create();
        }
        
        echo json_encode($response);
        
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update request", "success" => false]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update request. Data is incomplete.", "success" => false]);
}
?>