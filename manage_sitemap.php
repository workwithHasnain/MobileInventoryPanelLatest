<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once 'auth.php';
    require_once 'config.php';
    require_once 'database_functions.php';

    // Require login
    requireLogin();

    header('Content-Type: application/json');

    $sitemap_file = __DIR__ . '/sitemap.xml';
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'get') {
        // Return sitemap contents
        if (!file_exists($sitemap_file)) {
            echo json_encode(['success' => false, 'message' => 'Sitemap file not found']);
            exit;
        }
        $content = file_get_contents($sitemap_file);
        echo json_encode(['success' => true, 'content' => $content]);
        exit;
    } elseif ($action === 'save') {
        // Save manually edited sitemap
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Only administrators can modify the sitemap']);
            exit;
        }

        $content = file_get_contents('php://input');
        if (empty(trim($content))) {
            echo json_encode(['success' => false, 'message' => 'Sitemap content cannot be empty']);
            exit;
        }

        // Validate XML
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message);
            }
            libxml_clear_errors();
            echo json_encode(['success' => false, 'message' => 'Invalid XML: ' . implode('; ', $errors)]);
            exit;
        }

        if (!is_writable($sitemap_file)) {
            echo json_encode(['success' => false, 'message' => 'Sitemap file is not writable. Check file permissions.']);
            exit;
        }

        if (file_put_contents($sitemap_file, $content) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to write sitemap file']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Sitemap saved successfully']);
        exit;
} elseif ($action === 'sync') {
    // Sync sitemap: fetch all published posts and devices, rebuild sitemap
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only administrators can sync the sitemap']);
        exit;
    }

    try {
        $pdo = getConnection();

        // Read canonical base from config
        $configContent = file_get_contents(__DIR__ . '/config.php');
        $sitemapBase = 'https://www.devicesarena.com';
        if (preg_match('/\$canonicalBase\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $configContent, $matches)) {
            $sitemapBase = $matches[1];
        }

        $today = date('Y-m-d');

        // Start building sitemap XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Static pages
        $staticPages = [
            ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => '/phonefinder', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => '/compare', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => '/featured', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => '/reviews', 'changefreq' => 'daily', 'priority' => '0.8'],
        ];

        foreach ($staticPages as $page) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemapBase . $page['loc']) . "</loc>\n";
            $xml .= "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $page['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        // Fetch all published posts with slugs
        $postCount = 0;
        $stmt = $pdo->prepare("
            SELECT slug, updated_at, created_at 
            FROM posts 
            WHERE status ILIKE 'published' AND slug IS NOT NULL AND slug != ''
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($posts as $post) {
            $lastmod = !empty($post['updated_at']) ? date('Y-m-d', strtotime($post['updated_at'])) : (!empty($post['created_at']) ? date('Y-m-d', strtotime($post['created_at'])) : $today);
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemapBase . '/post/' . $post['slug']) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.7</priority>\n";
            $xml .= "  </url>\n";
            $postCount++;
        }

        // Fetch all devices with slugs
        $deviceCount = 0;
        $stmt = $pdo->prepare("
            SELECT slug, updated_at, created_at 
            FROM phones 
            WHERE slug IS NOT NULL AND slug != ''
            ORDER BY name ASC
        ");
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($devices as $device) {
            $lastmod = !empty($device['updated_at']) ? date('Y-m-d', strtotime($device['updated_at'])) : (!empty($device['created_at']) ? date('Y-m-d', strtotime($device['created_at'])) : $today);
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemapBase . '/device/' . $device['slug']) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
            $deviceCount++;
        }

        $xml .= "</urlset>\n";

        // Write sitemap
        if (file_put_contents($sitemap_file, $xml) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to write sitemap file']);
            exit;
        }

        $totalUrls = count($staticPages) + $postCount + $deviceCount;
        echo json_encode([
            'success' => true,
            'message' => "Sitemap synced successfully. Total URLs: {$totalUrls} (5 static pages, {$postCount} posts, {$deviceCount} devices)",
            'content' => $xml
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
