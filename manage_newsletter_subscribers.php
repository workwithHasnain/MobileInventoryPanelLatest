<?php

/**
 * Newsletter Subscribers Management Handler - AJAX endpoint
 */

header('Content-Type: application/json');
require_once 'database_functions.php';

$response = [
    'success' => false,
    'message' => ''
];

$action = $_POST['action'] ?? '';

try {
    $pdo = getConnection();

    if ($action === 'list') {
        // Get all newsletter subscribers
        $stmt = $pdo->prepare("SELECT id, email, status, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
        $stmt->execute();
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['subscribers'] = $subscribers;
        $response['count'] = count($subscribers);
    } elseif ($action === 'add') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Please enter a valid email address.';
        } else {
            // Check if email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $check_stmt->execute([$email]);

            if ($check_stmt->fetch()) {
                $response['message'] = 'This email is already subscribed.';
            } else {
                // Add new subscriber
                $insert_stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, status) VALUES (?, 'active')");
                $insert_stmt->execute([$email]);
                $response['success'] = true;
                $response['message'] = 'Subscriber added successfully!';
            }
        }
    } elseif ($action === 'remove') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            $response['message'] = 'Invalid subscriber ID.';
        } else {
            $delete_stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
            $delete_stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = 'Subscriber removed successfully!';
        }
    } elseif ($action === 'status') {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($id <= 0 || !in_array($status, ['active', 'inactive'])) {
            $response['message'] = 'Invalid parameters.';
        } else {
            $update_stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = ? WHERE id = ?");
            $update_stmt->execute([$status, $id]);
            $response['success'] = true;
            $response['message'] = 'Status updated successfully!';
        }
    } elseif ($action === 'export') {
        // Export subscribers based on status
        $status = $_POST['status'] ?? 'all';

        if ($status === 'all') {
            $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers ORDER BY subscribed_at DESC");
            $stmt->execute();
        } else if (in_array($status, ['active', 'inactive'])) {
            $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers WHERE status = ? ORDER BY subscribed_at DESC");
            $stmt->execute([$status]);
        } else {
            $response['message'] = 'Invalid status parameter.';
            echo json_encode($response);
            exit;
        }

        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $emails = array_map(function ($subscriber) {
            return $subscriber['email'];
        }, $subscribers);

        // Return as plain text (comma-separated)
        header('Content-Type: text/plain; charset=utf-8');
        echo implode(', ', $emails);
        exit;
    } else {
        $response['message'] = 'Invalid action.';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
