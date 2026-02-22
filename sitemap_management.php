<?php

/**
 * Sitemap Management Utility Functions
 * Handles adding, updating, and removing URLs from sitemap.xml
 */

require_once 'config.php';

/**
 * Get all existing URLs from sitemap.xml
 * 
 * @return array Array of URLs currently in sitemap
 */
function getSitemapUrls()
{
    $sitemap_file = __DIR__ . '/sitemap.xml';

    if (!file_exists($sitemap_file)) {
        return [];
    }

    $sitemap_content = file_get_contents($sitemap_file);
    $existing_urls = [];

    preg_match_all('/<loc>(.*?)<\/loc>/i', $sitemap_content, $matches);
    if (!empty($matches[1])) {
        $existing_urls = $matches[1];
    }

    return $existing_urls;
}

/**
 * Check if a URL already exists in sitemap
 * 
 * @param string $url The URL to check
 * @return bool True if URL exists, false otherwise
 */
function urlExistsInSitemap($url)
{
    $existing_urls = getSitemapUrls();
    return in_array($url, $existing_urls);
}

/**
 * Add a post URL to sitemap.xml
 * 
 * @param string $slug The post slug
 * @param string $lastmod The last modified date (Y-m-d format)
 * @return bool True on success, false on failure
 */
function addPostToSitemap($slug, $lastmod = null)
{
    global $canonicalBase;

    if (empty($slug)) {
        return false;
    }

    $sitemap_file = __DIR__ . '/sitemap.xml';

    if (!file_exists($sitemap_file)) {
        return false;
    }

    $post_url = $canonicalBase . '/post/' . urlencode($slug);

    // Check if URL already exists
    if (urlExistsInSitemap($post_url)) {
        return true; // URL already in sitemap, return success
    }

    $sitemap_content = file_get_contents($sitemap_file);

    if ($lastmod === null) {
        $lastmod = date('Y-m-d');
    }

    $new_entry = "    <url>\n";
    $new_entry .= "        <loc>" . htmlspecialchars($post_url, ENT_XML1) . "</loc>\n";
    $new_entry .= "        <lastmod>" . $lastmod . "</lastmod>\n";
    $new_entry .= "        <changefreq>weekly</changefreq>\n";
    $new_entry .= "        <priority>0.8</priority>\n";
    $new_entry .= "    </url>\n";

    // Insert before closing </urlset>
    $updated_sitemap = preg_replace('/<\/urlset>\s*$/i', $new_entry . '</urlset>', $sitemap_content);

    return file_put_contents($sitemap_file, $updated_sitemap) !== false;
}

/**
 * Remove a post URL from sitemap.xml
 * 
 * @param string $slug The post slug
 * @return bool True on success, false on failure
 */
function removePostFromSitemap($slug)
{
    global $canonicalBase;

    if (empty($slug)) {
        return false;
    }

    $sitemap_file = __DIR__ . '/sitemap.xml';

    if (!file_exists($sitemap_file)) {
        return false;
    }

    $post_url = $canonicalBase . '/post/' . urlencode($slug);
    $sitemap_content = file_get_contents($sitemap_file);

    // Find and remove the URL entry
    $pattern = '/<url>\s*<loc>' . preg_quote($post_url, '/') . '<\/loc>.*?<\/url>\s*/is';
    $updated_sitemap = preg_replace($pattern, '', $sitemap_content);

    // Only save if content actually changed
    if ($updated_sitemap !== $sitemap_content) {
        return file_put_contents($sitemap_file, $updated_sitemap) !== false;
    }

    return false;
}

/**
 * Update a post URL in sitemap.xml (for slug changes)
 * 
 * @param string $old_slug The old post slug
 * @param string $new_slug The new post slug
 * @param string $lastmod The last modified date (Y-m-d format)
 * @return bool True on success, false on failure
 */
function updatePostInSitemap($old_slug, $new_slug, $lastmod = null)
{
    global $canonicalBase;

    if (empty($old_slug) || empty($new_slug)) {
        return false;
    }

    $sitemap_file = __DIR__ . '/sitemap.xml';

    if (!file_exists($sitemap_file)) {
        return false;
    }

    $old_url = $canonicalBase . '/post/' . urlencode($old_slug);
    $new_url = $canonicalBase . '/post/' . urlencode($new_slug);

    // If URLs are the same, no need to update
    if ($old_url === $new_url) {
        return true;
    }

    $sitemap_content = file_get_contents($sitemap_file);

    if ($lastmod === null) {
        $lastmod = date('Y-m-d');
    }

    // Find the old URL entry and replace it
    $pattern = '/<url>\s*<loc>' . preg_quote($old_url, '/') . '<\/loc>\s*<lastmod>.*?<\/lastmod>\s*<changefreq>.*?<\/changefreq>\s*<priority>.*?<\/priority>\s*<\/url>/is';

    $new_entry = "<url>\n        <loc>" . htmlspecialchars($new_url, ENT_XML1) . "</loc>\n        <lastmod>" . $lastmod . "</lastmod>\n        <changefreq>weekly</changefreq>\n        <priority>0.8</priority>\n    </url>";

    $updated_sitemap = preg_replace($pattern, $new_entry, $sitemap_content);

    // Only save if content actually changed
    if ($updated_sitemap !== $sitemap_content) {
        return file_put_contents($sitemap_file, $updated_sitemap) !== false;
    }

    return false;
}

/**
 * Update lastmod date for a post URL in sitemap
 * 
 * @param string $slug The post slug
 * @param string $lastmod The last modified date (Y-m-d format)
 * @return bool True on success, false on failure
 */
function updatePostLastmodInSitemap($slug, $lastmod = null)
{
    global $canonicalBase;

    if (empty($slug)) {
        return false;
    }

    $sitemap_file = __DIR__ . '/sitemap.xml';

    if (!file_exists($sitemap_file)) {
        return false;
    }

    $post_url = $canonicalBase . '/post/' . urlencode($slug);

    if ($lastmod === null) {
        $lastmod = date('Y-m-d');
    }

    $sitemap_content = file_get_contents($sitemap_file);

    // Find and update the lastmod for this URL
    $pattern = '/<loc>' . preg_quote($post_url, '/') . '<\/loc>\s*<lastmod>.*?<\/lastmod>/i';
    $replacement = '<loc>' . htmlspecialchars($post_url, ENT_XML1) . '</loc><lastmod>' . $lastmod . '</lastmod>';

    $updated_sitemap = preg_replace($pattern, $replacement, $sitemap_content);

    // Only save if content actually changed
    if ($updated_sitemap !== $sitemap_content) {
        return file_put_contents($sitemap_file, $updated_sitemap) !== false;
    }

    return false;
}
