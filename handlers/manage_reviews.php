<?php

/**
 * Manage Reviews
 * Handles CRUD operations for phone reviews
 */

require_once 'config.php';
require_once 'database_functions.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'list':
            listReviews();
            break;
        case 'add':
            addReview();
            break;
        case 'delete':
            deleteReview();
            break;
        case 'search_phones':
            searchPhones();
            break;
        case 'search_posts':
            searchPosts();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function listReviews()
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.phone_id,
                r.post_id,
                p.name as phone_name,
                p.image as phone_image,
                po.title as post_title,
                po.featured_image as post_image
            FROM reviews r
            INNER JOIN phones p ON r.phone_id = p.id
            INNER JOIN posts po ON r.post_id = po.id
            ORDER BY r.id DESC
        ");

        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'reviews' => $reviews]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addReview()
{
    global $pdo;

    try {
        $phone_id = $_POST['phone_id'] ?? null;
        $post_id = $_POST['post_id'] ?? null;

        if (!$phone_id || !$post_id) {
            echo json_encode(['success' => false, 'message' => 'Phone ID and Post ID are required']);
            return;
        }

        // Check if phone exists
        $stmt = $pdo->prepare("SELECT id FROM phones WHERE id = ?");
        $stmt->execute([$phone_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Phone not found']);
            return;
        }

        // Check if post exists
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            return;
        }

        // Check if phone already has a review
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE phone_id = ?");
        $stmt->execute([$phone_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This phone already has a review']);
            return;
        }

        // Check if post already has a review
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE post_id = ?");
        $stmt->execute([$post_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This post already has a review']);
            return;
        }

        // Insert the review
        $stmt = $pdo->prepare("
            INSERT INTO reviews (phone_id, post_id)
            VALUES (?, ?)
        ");

        $stmt->execute([$phone_id, $post_id]);

        echo json_encode(['success' => true, 'message' => 'Review created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteReview()
{
    global $pdo;

    try {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Review ID is required']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function searchPhones()
{
    global $pdo;

    try {
        $term = $_GET['term'] ?? '';

        $stmt = $pdo->prepare("
            SELECT 
                id,
                name as text,
                image
            FROM phones
            WHERE name ILIKE ? OR brand ILIKE ?
            ORDER BY name
            LIMIT 10
        ");

        $searchTerm = "%{$term}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row['id'],
                'text' => $row['text'],
                'image' => $row['image'] ?: 'https://via.placeholder.com/50'
            ];
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function searchPosts()
{
    global $pdo;

    try {
        $term = $_GET['term'] ?? '';

        $stmt = $pdo->prepare("
            SELECT 
                id,
                title as text,
                featured_image
            FROM posts
            WHERE title ILIKE ? OR short_description ILIKE ?
            ORDER BY title
            LIMIT 10
        ");

        $searchTerm = "%{$term}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row['id'],
                'text' => $row['text'],
                'image' => $row['featured_image'] ?: 'https://via.placeholder.com/50'
            ];
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
