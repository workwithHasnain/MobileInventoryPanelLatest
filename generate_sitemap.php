<?php

/**
 * Sitemap Generator - Creates SEO-optimized XML sitemap
 * This script generates a sitemap.xml with all posts, devices, and static pages
 * Follows sitemaps.org protocol for optimal crawler compatibility
 */

require_once 'database_functions.php';
require_once 'config.php';

// Get canonical base from config
global $canonicalBase;

// Start building XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset/>');
$xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$xml->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

// Helper function to add URL to sitemap
function addUrl($xml, $loc, $lastmod = null, $changefreq = 'weekly', $priority = '0.8')
{
    $url = $xml->addChild('url');
    $url->addChild('loc', htmlspecialchars($loc, ENT_XML1, 'UTF-8'));

    if ($lastmod) {
        $url->addChild('lastmod', $lastmod);
    }

    $url->addChild('changefreq', $changefreq);
    $url->addChild('priority', $priority);
}

try {
    // 1. ADD STATIC PAGES
    // Homepage - highest priority
    addUrl($xml, $canonicalBase . '/', date('Y-m-d'), 'daily', '1.0');

    // Featured page
    addUrl($xml, $canonicalBase . '/featured', date('Y-m-d'), 'weekly', '0.9');

    // Phone Finder
    addUrl($xml, $canonicalBase . '/phonefinder', date('Y-m-d'), 'weekly', '0.9');

    // Reviews
    addUrl($xml, $canonicalBase . '/reviews', date('Y-m-d'), 'weekly', '0.8');

    // Compare (base page)
    addUrl($xml, $canonicalBase . '/compare', date('Y-m-d'), 'monthly', '0.7');

    // 2. ADD ALL PUBLISHED POSTS
    $pdo = getConnection();
    $posts_stmt = $pdo->prepare("
        SELECT slug, updated_at, created_at 
        FROM posts 
        WHERE status ILIKE 'published' 
        ORDER BY updated_at DESC
    ");
    $posts_stmt->execute();
    $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as $post) {
        $lastmod = !empty($post['updated_at']) ? substr($post['updated_at'], 0, 10) : substr($post['created_at'], 0, 10);
        addUrl(
            $xml,
            $canonicalBase . '/post/' . urlencode($post['slug']),
            $lastmod,
            'monthly',
            '0.8'
        );
    }

    // 3. ADD ALL DEVICES
    $devices_stmt = $pdo->prepare("
        SELECT slug, updated_at, created_at 
        FROM phones 
        WHERE slug IS NOT NULL AND slug != '' 
        ORDER BY updated_at DESC
    ");
    $devices_stmt->execute();
    $devices = $devices_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($devices as $device) {
        $lastmod = !empty($device['updated_at']) ? substr($device['updated_at'], 0, 10) : substr($device['created_at'], 0, 10);
        addUrl(
            $xml,
            $canonicalBase . '/device/' . urlencode($device['slug']),
            $lastmod,
            'monthly',
            '0.7'
        );
    }

    // 4. SAVE SITEMAP FILE
    $sitemap_path = __DIR__ . '/sitemap.xml';

    // Format the XML nicely
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());

    $success = $dom->save($sitemap_path);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Sitemap generated successfully',
            'stats' => [
                'posts' => count($posts),
                'devices' => count($devices),
                'total_urls' => 5 + count($posts) + count($devices)
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save sitemap file'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating sitemap: ' . $e->getMessage()
    ]);
}
