<?php

/**
 * Test Post Display Script
 * Shows raw database content_body of a post
 * Usage: test-post.php?slug=post-slug-name
 */

require_once 'database_functions.php';

// Get post ID from URL
$post_slug = $_GET['slug'] ?? null;

if (!$post_slug) {
    echo "Error: Post slug not provided.";
    exit;
}

try {
    $pdo = getConnection();

    // Fetch the post
    $stmt = $pdo->prepare("SELECT content_body FROM posts WHERE slug = ?");
    $stmt->execute([$post_slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo "Error: Post with slug {$post_slug} not found.";
        exit;
    }

    // Echo raw content_body directly
    echo $post['content_body'];
} catch (PDOException $e) {
    echo "Database Error: " . htmlspecialchars($e->getMessage());
    exit;
}
