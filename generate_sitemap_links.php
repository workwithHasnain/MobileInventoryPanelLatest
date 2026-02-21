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
$existing_urls = [];
preg_match_all('/<loc>(.*?)<\/loc>/i', $sitemap_content, $matches);
if (!empty($matches[1])) {
    $existing_urls = $matches[1];
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
        $device_url = $canonicalBase . '/device/' . urlencode($device['slug']);

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
        $post_url = $canonicalBase . '/post/' . urlencode($post['slug']);

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
    $new_xml = '';
    
    foreach ($new_entries as $entry) {
        $new_xml .= "    <url>\n";
        $new_xml .= "        <loc>" . htmlspecialchars($entry['loc'], ENT_XML1) . "</loc>\n";
        $new_xml .= "        <lastmod>" . $entry['lastmod'] . "</lastmod>\n";
        $new_xml .= "        <changefreq>" . $entry['changefreq'] . "</changefreq>\n";
        $new_xml .= "        <priority>" . $entry['priority'] . "</priority>\n";
        $new_xml .= "    </url>\n";
    }
    
    // Insert before closing </urlset> - handle whitespace
    $updated_sitemap = preg_replace('/<\/urlset>\s*$/i', $new_xml . '</urlset>', $sitemap_content);
    
    if ($updated_sitemap === null) {
        die('ERROR: Regex replacement failed');
    }
    
    if (file_put_contents($sitemap_file, $updated_sitemap, LOCK_EX) === false) {
        die('ERROR: Failed to write sitemap file - check file permissions (chmod 644 or 666)');
    }

    echo 'SUCCESS: Added ' . count($new_entries) . ' new links to sitemap';
} else {
    echo 'INFO: No new links to add (all existing or duplicates)';
}
