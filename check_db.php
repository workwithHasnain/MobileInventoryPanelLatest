<?php
require 'database_functions.php';
$pdo = getConnection();

// Search for the post with the specific text
$stmt = $pdo->query("SELECT id, title, status, content_body FROM posts WHERE content_body LIKE '%ONCE YOU USE%' OR content_body LIKE '%GOOD LIGHTING%' OR content_body LIKE '%PERFORMANCE WITHOUT%' LIMIT 1");
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if ($post) {
    echo "=== FOUND POST ===\n\n";
    echo "ID: " . $post['id'] . "\n";
    echo "Title: " . $post['title'] . "\n";
    echo "Status: " . $post['status'] . "\n\n";
    echo "FULL Content:\n";
    echo "---\n";
    echo $post['content_body'];
    echo "\n---\n";
} else {
    echo "Post not found. Showing all posts with content:\n\n";
    $stmt = $pdo->query("SELECT id, title, LENGTH(content_body) as len FROM posts WHERE content_body IS NOT NULL AND content_body != '' ORDER BY LENGTH(content_body) DESC");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $row) {
        echo "ID: " . $row['id'] . " | Content length: " . $row['len'] . " | Title: " . $row['title'] . "\n";
    }
}
?>
