<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'database_functions.php';

requireLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('ERROR: Admin only');
}

$current_sitemap = $_POST['current_sitemap'] ?? '';

if (empty($current_sitemap)) {
    die('ERROR: No sitemap provided');
}

// Suppress XML warnings  
libxml_use_internal_errors(true);

// Parse existing sitemap to extract current URLs
$existing_urls = [];
$existing_doc = new DOMDocument();
$existing_doc->preserveWhiteSpace = false;

if (@$existing_doc->loadXML($current_sitemap)) {
    $urls = $existing_doc->getElementsByTagName('loc');
    foreach ($urls as $url) {
        $existing_urls[] = trim($url->nodeValue);
    }
}

libxml_clear_errors();

// Get database connection
$pdo = getConnection();

// Fetch all published posts
$posts = [];
try {
    $stmt = $pdo->prepare("
        SELECT slug, updated_at FROM posts 
        WHERE status ILIKE 'published' 
        ORDER BY updated_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching posts: ' . $e->getMessage());
}

// Fetch all devices
$devices = [];
try {
    $devices = getAllPhones();
    if (!is_array($devices)) {
        $devices = [];
    }
} catch (Exception $e) {
    error_log('Error fetching devices: ' . $e->getMessage());
}

$base_url = $canonicalBase ?? 'https://www.devicesarena.com';

// Collect new entries
$new_entries = [];

// Add published posts
foreach ($posts as $post) {
    if (empty($post['slug'])) continue;
    $post_url = $base_url . '/post/' . $post['slug'];
    if (!in_array($post_url, $existing_urls)) {
        $last_mod = !empty($post['updated_at']) ? date('Y-m-d', strtotime($post['updated_at'])) : date('Y-m-d');
        $new_entries[] = [
            'loc' => $post_url,
            'lastmod' => $last_mod,
            'changefreq' => 'weekly',
            'priority' => '0.7'
        ];
    }
}

// Add devices
foreach ($devices as $device) {
    if (empty($device['slug'])) continue;
    $device_url = $base_url . '/device/' . $device['slug'];
    if (!in_array($device_url, $existing_urls)) {
        $last_mod = date('Y-m-d');
        $new_entries[] = [
            'loc' => $device_url,
            'lastmod' => $last_mod,
            'changefreq' => 'monthly',
            'priority' => '0.8'
        ];
    }
}

// If no new entries, return original sitemap unchanged
if (empty($new_entries)) {
    echo $current_sitemap;
    libxml_use_internal_errors(false);
    exit;
}

// Parse sitemap for modification - with error checking
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;

if (!@$dom->loadXML($current_sitemap)) {
    // If XML load fails, return original  
    error_log('Failed to parse sitemap XML for modification');
    echo $current_sitemap;
    libxml_use_internal_errors(false);
    exit;
}

$root = $dom->documentElement;
if (!$root) {
    // If no root element, return original
    error_log('No root element in sitemap');
    echo $current_sitemap;
    libxml_use_internal_errors(false);
    exit;
}

// Add new URL entries
foreach ($new_entries as $entry) {
    $url_node = $dom->createElement('url');
    
    $loc = $dom->createElement('loc', htmlspecialchars($entry['loc'], ENT_XML1));
    $url_node->appendChild($loc);
    
    $lastmod = $dom->createElement('lastmod', $entry['lastmod']);
    $url_node->appendChild($lastmod);
    
    $changefreq = $dom->createElement('changefreq', $entry['changefreq']);
    $url_node->appendChild($changefreq);
    
    $priority = $dom->createElement('priority', $entry['priority']);
    $url_node->appendChild($priority);
    
    $root->appendChild($url_node);
}

// Format and return
$dom->formatOutput = true;
$result = $dom->saveXML();

libxml_use_internal_errors(false);

if ($result === false) {
    error_log('Failed to save XML');
    echo $current_sitemap;
} else {
    echo $result;
}

exit;
