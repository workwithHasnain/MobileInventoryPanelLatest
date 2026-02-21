<?php
require_once 'auth.php';
require_once 'database_functions.php';

requireLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('ERROR: Admin only');
}

$current_sitemap = $_POST['current_sitemap'] ?? '';

if (empty($current_sitemap)) {
    die('ERROR: No sitemap provided');
}

// Parse existing sitemap
$existing_urls = [];
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;

if (@$dom->loadXML($current_sitemap)) {
    $urls = $dom->getElementsByTagName('loc');
    foreach ($urls as $url) {
        $existing_urls[] = $url->nodeValue;
    }
}

// Get database connection
$pdo = getConnection();

// Fetch all published posts
$posts = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, slug, updated_at FROM posts 
        WHERE status ILIKE 'published' 
        ORDER BY updated_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('ERROR: Failed to fetch posts');
}

// Fetch all devices
$devices = [];
try {
    $devices = getAllPhones();
} catch (Exception $e) {
    die('ERROR: Failed to fetch devices');
}

// Get canonicalBase from config
require_once 'config.php';
$base_url = $canonicalBase;

// Generate new URLs
$new_entries = [];

// Add published posts
foreach ($posts as $post) {
    $post_url = $base_url . '/post/' . $post['slug'];
    if (!in_array($post_url, $existing_urls)) {
        $last_mod = date('Y-m-d', strtotime($post['updated_at']));
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

// If no new entries, return as-is
if (empty($new_entries)) {
    echo $current_sitemap;
    exit;
}

// Parse and update sitemap with new entries
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->loadXML($current_sitemap);

$root = $dom->documentElement;

// Add new URL entries
foreach ($new_entries as $entry) {
    $url_node = $dom->createElement('url');

    $loc = $dom->createElement('loc');
    $loc->appendChild($dom->createTextNode($entry['loc']));
    $url_node->appendChild($loc);

    $lastmod = $dom->createElement('lastmod');
    $lastmod->appendChild($dom->createTextNode($entry['lastmod']));
    $url_node->appendChild($lastmod);

    $changefreq = $dom->createElement('changefreq');
    $changefreq->appendChild($dom->createTextNode($entry['changefreq']));
    $url_node->appendChild($changefreq);

    $priority = $dom->createElement('priority');
    $priority->appendChild($dom->createTextNode($entry['priority']));
    $url_node->appendChild($priority);

    $root->appendChild($url_node);
}

// Format and return
$dom->formatOutput = true;
echo $dom->saveXML();
exit;
