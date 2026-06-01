<?php

/**
 * Newsletter Handler - AJAX endpoint
 * Handles newsletter subscription without page reload
 */

header('Content-Type: application/json');
require_once 'database_functions.php';

$response = [
    'success' => false,
    'message' => ''
];

if ($_POST && isset($_POST['newsletter_email'])) {
    $email = trim($_POST['newsletter_email'] ?? '');

    if (empty($email)) {
        $response['message'] = 'Email is required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo = getConnection();

        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
        $check_stmt->execute([$email]);
        $existing = $check_stmt->fetch();

        if ($existing) {
            $response['success'] = true;
            $response['message'] = 'You are already subscribed to our newsletter!';
        } else {
            // Insert new subscriber
            $insert_stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, status) VALUES (?, 'active')");
            $insert_stmt->execute([$email]);
            $response['success'] = true;
            $response['message'] = 'Thank you for subscribing to our newsletter!';
        }
    } catch (PDOException $e) {
        // Check if it's a unique constraint violation
        if (strpos($e->getMessage(), 'unique') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
            $response['success'] = true;
            $response['message'] = 'You are already subscribed to our newsletter!';
        } else {
            $response['message'] = 'There was an error processing your subscription. Please try again.';
        }
    } catch (Exception $e) {
        $response['message'] = 'There was an error processing your subscription. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
