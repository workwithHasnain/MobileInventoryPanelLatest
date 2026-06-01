<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

// Require admin role for editing
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Only administrators can modify settings']);
        exit;
    }
}

header('Content-Type: application/json');

$config_file = __DIR__ . '/config.php';

if ($_POST['action'] === 'get') {
    // Read canonical base from config.php
    if (!file_exists($config_file)) {
        echo json_encode(['success' => false, 'message' => 'Config file not found']);
        exit;
    }

    // Read the file and extract the canonical base value
    $config_content = file_get_contents($config_file);
    
    // Match the pattern: $canonicalBase = 'value';
    if (preg_match('/\$canonicalBase\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $config_content, $matches)) {
        $canonicalBase = $matches[1];
        echo json_encode(['success' => true, 'canonicalBase' => $canonicalBase]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not find canonical base in config']);
    }
    exit;

} elseif ($_POST['action'] === 'save') {
    // Save canonical base to config.php
    if (!isset($_POST['canonicalBase'])) {
        echo json_encode(['success' => false, 'message' => 'Canonical base URL is required']);
        exit;
    }

    $newValue = trim($_POST['canonicalBase']);

    // Validation
    if (empty($newValue)) {
        echo json_encode(['success' => false, 'message' => 'Canonical base URL cannot be empty']);
        exit;
    }

    if (!filter_var($newValue, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
        exit;
    }

    if (substr($newValue, -1) === '/') {
        echo json_encode(['success' => false, 'message' => 'URL should not have a trailing slash']);
        exit;
    }

    // Read config file
    if (!file_exists($config_file)) {
        echo json_encode(['success' => false, 'message' => 'Config file not found']);
        exit;
    }

    $config_content = file_get_contents($config_file);

    // Replace the canonical base value
    $new_content = preg_replace(
        '/\$canonicalBase\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/',
        "\$canonicalBase = '" . addslashes($newValue) . "';",
        $config_content
    );

    // Check if replacement was made
    if ($new_content === $config_content) {
        echo json_encode(['success' => false, 'message' => 'Could not find canonical base in config file']);
        exit;
    }

    // Write back to file
    if (!is_writable($config_file)) {
        echo json_encode(['success' => false, 'message' => 'Config file is not writable. Please check file permissions.']);
        exit;
    }

    if (file_put_contents($config_file, $new_content) === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to write to config file']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Canonical base URL updated successfully']);
    exit;

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
?>
