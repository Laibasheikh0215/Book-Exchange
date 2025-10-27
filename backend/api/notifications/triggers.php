<?php
// Notification triggers - include this in other API files
function sendNotification($db, $user_id, $title, $message, $type = 'System', $related_id = null) {
    $notification = new Notification($db);
    
    $notification->user_id = $user_id;
    $notification->title = $title;
    $notification->message = $message;
    $notification->type = $type;
    $notification->related_id = $related_id;
    
    return $notification->create();
}

// Request created - notify book owner
function notifyRequestCreated($db, $request_id, $book_owner_id, $requester_name, $book_title) {
    return sendNotification(
        $db,
        $book_owner_id,
        "New Book Request",
        "{$requester_name} wants to borrow your book '{$book_title}'",
        'Request',
        $request_id
    );
}

// Request accepted - notify requester
function notifyRequestAccepted($db, $request_id, $requester_id, $owner_name, $book_title) {
    return sendNotification(
        $db,
        $requester_id,
        "Request Accepted!",
        "{$owner_name} accepted your request for '{$book_title}'",
        'Request',
        $request_id
    );
}

// Request rejected - notify requester
function notifyRequestRejected($db, $request_id, $requester_id, $owner_name, $book_title) {
    return sendNotification(
        $db,
        $requester_id,
        "Request Declined",
        "{$owner_name} declined your request for '{$book_title}'",
        'Request',
        $request_id
    );
}

// Return date reminder
function notifyReturnReminder($db, $request_id, $user_id, $book_title, $return_date) {
    return sendNotification(
        $db,
        $user_id,
        "Return Reminder",
        "Don't forget to return '{$book_title}' by {$return_date}",
        'Reminder',
        $request_id
    );
}

// New message notification
function notifyNewMessage($db, $receiver_id, $sender_name, $message_preview) {
    return sendNotification(
        $db,
        $receiver_id,
        "New Message from {$sender_name}",
        $message_preview,
        'Message',
        null
    );
}
?>