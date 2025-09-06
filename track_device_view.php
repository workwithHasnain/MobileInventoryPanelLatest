<?php
require_once 'database_functions.php';
$pdo = getConnection();
require_once 'includes/database_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device_id'])) {
    $deviceId = (int)$_POST['device_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    try {
        // Track the device view
        trackDeviceView($deviceId, $ipAddress);
        
        echo json_encode(['success' => true, 'message' => 'View tracked successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to track view']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>