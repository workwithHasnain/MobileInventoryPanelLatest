<?php
require_once 'auth.php';

// Require login for this page
requireLogin();

// Require admin role for editing/syncing
if (isset($_POST['action']) && ($_POST['action'] === 'save' || $_POST['action'] === 'sync')) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Only administrators can modify settings']);
        exit;
    }
}

header('Content-Type: application/json');

$sitemap_file = __DIR__ . '/sitemap.xml';

if ($_POST['action'] === 'get') {
    // Read and return current sitemap
    if (!file_exists($sitemap_file)) {
        echo json_encode([
            'success' => false,
            'message' => 'Sitemap not found. Please generate a new one.',
            'content' => ''
        ]);
        exit;
    }

    $content = file_get_contents($sitemap_file);
    echo json_encode([
        'success' => true,
        'content' => $content,
        'lastModified' => date('Y-m-d H:i:s', filemtime($sitemap_file))
    ]);
    exit;
} elseif ($_POST['action'] === 'sync') {
    // Regenerate sitemap from database
    // This calls generate_sitemap.php without auth checks since we already validated

    require_once 'database_functions.php';
    require_once 'config.php';

    global $canonicalBase;

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset/>');
    $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

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
        // Add static pages
        addUrl($xml, $canonicalBase . '/', date('Y-m-d'), 'daily', '1.0');
        addUrl($xml, $canonicalBase . '/featured', date('Y-m-d'), 'weekly', '0.9');
        addUrl($xml, $canonicalBase . '/phonefinder', date('Y-m-d'), 'weekly', '0.9');
        addUrl($xml, $canonicalBase . '/reviews', date('Y-m-d'), 'weekly', '0.8');
        addUrl($xml, $canonicalBase . '/compare', date('Y-m-d'), 'monthly', '0.7');

        $pdo = getConnection();

        // Get all published posts
        $posts_stmt = $pdo->prepare("
            SELECT slug, updated_at, created_at 
            FROM posts 
            WHERE status ILIKE 'published' 
            ORDER BY updated_at DESC
        ");
        $posts_stmt->execute();
        $posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

        $post_count = 0;
        foreach ($posts as $post) {
            $lastmod = !empty($post['updated_at']) ? substr($post['updated_at'], 0, 10) : substr($post['created_at'], 0, 10);
            addUrl(
                $xml,
                $canonicalBase . '/post/' . urlencode($post['slug']),
                $lastmod,
                'monthly',
                '0.8'
            );
            $post_count++;
        }

        // Get all devices
        $devices_stmt = $pdo->prepare("
            SELECT slug, updated_at, created_at 
            FROM phones 
            WHERE slug IS NOT NULL AND slug != '' 
            ORDER BY updated_at DESC
        ");
        $devices_stmt->execute();
        $devices = $devices_stmt->fetchAll(PDO::FETCH_ASSOC);

        $device_count = 0;
        foreach ($devices as $device) {
            $lastmod = !empty($device['updated_at']) ? substr($device['updated_at'], 0, 10) : substr($device['created_at'], 0, 10);
            addUrl(
                $xml,
                $canonicalBase . '/device/' . urlencode($device['slug']),
                $lastmod,
                'monthly',
                '0.7'
            );
            $device_count++;
        }

        // Save with nice formatting
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        if (!is_writable(dirname($sitemap_file))) {
            echo json_encode([
                'success' => false,
                'message' => 'Sitemap directory is not writable. Check file permissions.'
            ]);
            exit;
        }

        $saved = $dom->save($sitemap_file);

        if ($saved) {
            echo json_encode([
                'success' => true,
                'message' => 'Sitemap synced successfully!',
                'stats' => [
                    'posts' => $post_count,
                    'devices' => $device_count,
                    'total_urls' => 5 + $post_count + $device_count
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to write sitemap file'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error syncing sitemap: ' . $e->getMessage()
        ]);
    }
    exit;
} elseif ($_POST['action'] === 'save') {
    // Save edited sitemap
    if (!isset($_POST['content']) || empty($_POST['content'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Sitemap content cannot be empty'
        ]);
        exit;
    }

    $content = $_POST['content'];

    // Basic XML validation
    $xml = @simplexml_load_string($content);
    if (!$xml) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid XML format. Please check your sitemap syntax.'
        ]);
        exit;
    }

    // Check if it has required sitemap structure
    if (!isset($xml->url)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid sitemap format. Must contain <url> elements.'
        ]);
        exit;
    }

    // Format XML nicely
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($content);

    if (!is_writable(dirname($sitemap_file))) {
        echo json_encode([
            'success' => false,
            'message' => 'Sitemap file is not writable. Check file permissions.'
        ]);
        exit;
    }

    if ($dom->save($sitemap_file) === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save sitemap file'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sitemap updated successfully!'
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
