<?php
// Handle notification flag updates via AJAX

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Only process AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Only process if user is logged in or resetting
    if (!empty($_SESSION['public_user_id']) || $action === 'reset') {
        if ($action === 'mark_seen') {
            // Mark notifications as seen
            $_SESSION['notif_seen'] = true;
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Notifications marked as seen']);
        } elseif ($action === 'reset') {
            // Reset notification flag on logout
            $_SESSION['notif_seen'] = false;
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Notifications reset']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
