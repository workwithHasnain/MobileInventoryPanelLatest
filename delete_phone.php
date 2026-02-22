<?php
require_once 'auth.php';
require_once 'phone_data.php';
require_once 'sitemap_management.php'; // Add sitemap management functions

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

// Store slug before deletion for sitemap removal
$phone_slug = $phone['slug'] ?? '';

// Delete image file if it exists
if (!empty($phone['image']) && file_exists($phone['image'])) {
    unlink($phone['image']);
}

// Delete phone from data
if (deletePhone($id)) {
    // Remove from sitemap if it has a slug
    if (!empty($phone_slug)) {
        removeDeviceFromSitemap($phone_slug);
    }
}

// Redirect to devices page
header('Location: devices.php');
exit();
