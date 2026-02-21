<?php
require_once 'auth.php';
requireLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('ERROR: Admin only');
}

$file = __DIR__ . '/sitemap.xml';
$action = $_POST['action'] ?? '';

if ($action === 'read') {
    if (file_exists($file)) {
        echo file_get_contents($file);
    } else {
        die('ERROR: File not found');
    }
    exit;
}

if ($action === 'save') {
    $content = $_POST['content'] ?? '';

    if (empty($content)) {
        die('ERROR: Content is empty');
    }

    if (file_put_contents($file, $content) === false) {
        die('ERROR: Failed to save file');
    }

    echo 'SUCCESS: Sitemap saved';
    exit;
}

die('ERROR: Invalid action');
?>