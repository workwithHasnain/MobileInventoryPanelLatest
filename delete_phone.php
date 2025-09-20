<?php
require_once 'auth.php';
require_once 'phone_data.php';

// Require admin privileges for this page
requireAdmin();

// Get phone ID from URL parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;

// Check if phone exists
$phone = getPhoneById($id);
if (!$phone) {
    $_SESSION['success_message'] = 'Phone not found!';
    header('Location: devices.php');
    exit();
}

// Delete image file if it exists
if (!empty($phone['image']) && file_exists($phone['image'])) {
    unlink($phone['image']);
}

// Delete phone from data
if (deletePhone($id)) {
    $_SESSION['success_message'] = 'Phone deleted successfully!';
} else {
    $_SESSION['success_message'] = 'Failed to delete phone.';
}

// Redirect to devices page
header('Location: devices.php');
exit();
