<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

// Require admin role for both read and write
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('ERROR: Only administrators can access sitemap settings');
}

$sitemap_file = __DIR__ . '/sitemap.xml';

if ($_POST['action'] === 'read') {
    // Read sitemap.xml content
    if (!file_exists($sitemap_file)) {
        die('ERROR: Sitemap file not found');
    }

    if (!is_readable($sitemap_file)) {
        die('ERROR: Sitemap file is not readable');
    }

    $content = file_get_contents($sitemap_file);

    if ($content === false) {
        die('ERROR: Failed to read sitemap file');
    }

    echo $content;
    exit;
} elseif ($_POST['action'] === 'save') {
    // Save and validate sitemap.xml
    if (!isset($_POST['content'])) {
        die('ERROR: Sitemap content is required');
    }

    $content = trim($_POST['content']);

    if (empty($content)) {
        die('ERROR: Sitemap content cannot be empty');
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
        die('ERROR: ' . $error_msg);
    }

    // Validate that it's a urlset element (sitemap structure)
    $root = $dom->documentElement;
    if ($root->tagName !== 'urlset') {
        die('ERROR: Root element must be "urlset". Invalid sitemap structure.');
    }

    // Validate namespace
    $ns = $root->getAttribute('xmlns');
    if ($ns !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
        die('ERROR: Invalid or missing sitemap namespace. Expected: http://www.sitemaps.org/schemas/sitemap/0.9');
    }

    // Check file permissions
    if (!is_writable($sitemap_file)) {
        die('ERROR: Sitemap file is not writable. Please check file permissions.');
    }

    // Format XML nicely before saving
    $dom->formatOutput = true;
    $formatted_content = $dom->saveXML();

    // Write to file
    if (file_put_contents($sitemap_file, $formatted_content) === false) {
        die('ERROR: Failed to write to sitemap file');
    }

    echo 'SUCCESS: Sitemap saved successfully and validated';
    exit;
} else {
    die('ERROR: Invalid action');
}
