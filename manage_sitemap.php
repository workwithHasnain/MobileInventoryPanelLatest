<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

// Require admin role for both read and write
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only administrators can access sitemap settings']);
    exit;
}

header('Content-Type: application/json');

$sitemap_file = __DIR__ . '/sitemap.xml';

if ($_POST['action'] === 'read') {
    // Read sitemap.xml content
    if (!file_exists($sitemap_file)) {
        echo json_encode(['success' => false, 'message' => 'Sitemap file not found']);
        exit;
    }

    if (!is_readable($sitemap_file)) {
        echo json_encode(['success' => false, 'message' => 'Sitemap file is not readable']);
        exit;
    }

    $content = file_get_contents($sitemap_file);

    if ($content === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to read sitemap file']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'content' => $content
    ]);
    exit;
} elseif ($_POST['action'] === 'save') {
    // Save and validate sitemap.xml
    if (!isset($_POST['content'])) {
        echo json_encode(['success' => false, 'message' => 'Sitemap content is required']);
        exit;
    }

    $content = trim($_POST['content']);

    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Sitemap content cannot be empty']);
        exit;
    }

    // Validate XML using DOMDocument
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    // Disable external entity loading for security
    $libxml_previous_state = libxml_disable_entity_loader(true);

    // Load and validate XML
    $xml_valid = @$dom->loadXML($content);

    // Re-enable entity loader
    libxml_disable_entity_loader($libxml_previous_state);

    if (!$xml_valid) {
        $errors = libxml_get_errors();
        $error_msg = 'XML validation failed: ';

        if (!empty($errors)) {
            $error_msg .= $errors[0]->message;
        } else {
            $error_msg .= 'Invalid XML format';
        }

        libxml_clear_errors();
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }

    // Validate that it's a urlset element (sitemap structure)
    $root = $dom->documentElement;
    if ($root->tagName !== 'urlset') {
        echo json_encode(['success' => false, 'message' => 'Root element must be "urlset". Invalid sitemap structure.']);
        exit;
    }

    // Validate namespace
    $ns = $root->getAttribute('xmlns');
    if ($ns !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing sitemap namespace. Expected: http://www.sitemaps.org/schemas/sitemap/0.9']);
        exit;
    }

    // Check file permissions
    if (!is_writable($sitemap_file)) {
        echo json_encode(['success' => false, 'message' => 'Sitemap file is not writable. Please check file permissions.']);
        exit;
    }

    // Format XML nicely before saving
    $dom->formatOutput = true;
    $formatted_content = $dom->saveXML();

    // Write to file
    if (file_put_contents($sitemap_file, $formatted_content) === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to write to sitemap file']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sitemap saved successfully and validated'
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
