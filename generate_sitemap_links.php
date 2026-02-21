<?php
require_once 'config.php';
require_once 'database_functions.php';
require_once 'phone_data.php';

/**
 * Auto-generate and append device and post links to sitemap.xml
 * Follows SEO best practices
 * Prevents duplicates
 */

$sitemap_file = __DIR__ . '/sitemap.xml';

// Read existing sitemap
if (!file_exists($sitemap_file)) {
    die('ERROR: sitemap.xml not found');
}

$sitemap_content = file_get_contents($sitemap_file);

// Parse existing sitemap to get current URLs
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->load($sitemap_file);

$existing_urls = [];
$urlset = $dom->getElementsByTagName('urlset')->item(0);

foreach ($dom->getElementsByTagName('loc') as $loc) {
    $existing_urls[] = $loc->nodeValue;
}

// Get devices from database
try {
    $pdo = getConnection();
    
    $devices_stmt = $pdo->query("SELECT slug, updated_at FROM phones ORDER BY updated_at DESC");
    $devices = $devices_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $posts_stmt = $pdo->query("SELECT slug, updated_at FROM posts WHERE status ILIKE 'published' ORDER BY updated_at DESC");
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('ERROR: Database connection failed - ' . $e->getMessage());
}

$new_entries = [];

// Process devices
if (!empty($devices)) {
    foreach ($devices as $device) {
        $device_url = $canonicalBase . '/device/' . htmlspecialchars($device['slug']);
        
        // Check if URL already exists
        if (in_array($device_url, $existing_urls)) {
            continue; // Skip duplicate
        }
        
        $lastmod = date('Y-m-d', strtotime($device['updated_at']));
        
        $new_entries[] = [
            'loc' => $device_url,
            'lastmod' => $lastmod,
            'changefreq' => 'weekly',
            'priority' => '0.7'
        ];
    }
}

// Process posts
if (!empty($posts)) {
    foreach ($posts as $post) {
        $post_url = $canonicalBase . '/post/' . htmlspecialchars($post['slug']);
        
        // Check if URL already exists
        if (in_array($post_url, $existing_urls)) {
            continue; // Skip duplicate
        }
        
        $lastmod = date('Y-m-d', strtotime($post['updated_at']));
        
        $new_entries[] = [
            'loc' => $post_url,
            'lastmod' => $lastmod,
            'changefreq' => 'weekly',
            'priority' => '0.8'
        ];
    }
}

// Add new entries to sitemap
if (!empty($new_entries)) {
    foreach ($new_entries as $entry) {
        $url_element = $dom->createElement('url');
        
        $loc = $dom->createElement('loc', $entry['loc']);
        $url_element->appendChild($loc);
        
        $lastmod = $dom->createElement('lastmod', $entry['lastmod']);
        $url_element->appendChild($lastmod);
        
        $changefreq = $dom->createElement('changefreq', $entry['changefreq']);
        $url_element->appendChild($changefreq);
        
        $priority = $dom->createElement('priority', $entry['priority']);
        $url_element->appendChild($priority);
        
        $urlset->appendChild($url_element);
    }
    
    // Format and save
    $dom->formatOutput = true;
    $dom->save($sitemap_file);
    
    echo 'SUCCESS: Added ' . count($new_entries) . ' new links to sitemap';
} else {
    echo 'INFO: No new links to add (all existing or duplicates)';
}
?>
