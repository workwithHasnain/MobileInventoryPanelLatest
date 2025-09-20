<?php
require_once 'includes/database_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device1_id']) && isset($_POST['device2_id'])) {
    $device1Id = (string)$_POST['device1_id'];
    $device2Id = (string)$_POST['device2_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    try {
        // Track the device comparison
        trackDeviceComparison($device1Id, $device2Id, $ipAddress);

        echo json_encode(['success' => true, 'message' => 'Comparison tracked successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to track comparison']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
