<?php
session_start();
header('Content-Type: application/json');
require_once 'database_functions.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$comment = trim($_POST['comment'] ?? '');
$captchaInput = strtolower(trim($_POST['captcha'] ?? ''));

// CAPTCHA validation
if (empty($captchaInput)) {
    $response['message'] = 'Please enter the CAPTCHA text';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['captcha']) || $captchaInput !== $_SESSION['captcha']) {
    // Clear the used CAPTCHA so it can't be retried
    unset($_SESSION['captcha']);
    $response['message'] = 'CAPTCHA verification failed. Please try again.';
    $response['captcha_failed'] = true;
    echo json_encode($response);
    exit;
}

// Clear CAPTCHA after successful validation (one-time use)
unset($_SESSION['captcha']);

// Validation
if (empty($name)) {
    $response['message'] = 'Name is required';
    echo json_encode($response);
    exit;
}

if (empty($comment)) {
    $response['message'] = 'Comment is required';
    echo json_encode($response);
    exit;
}

if (strlen($comment) < 10) {
    $response['message'] = 'Comment must be at least 10 characters long';
    echo json_encode($response);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address';
    echo json_encode($response);
    exit;
}

try {
    $pdo = getConnection();

    if ($action === 'comment_device') {
        $device_id = $_POST['device_id'] ?? '';
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

        if (empty($device_id)) {
            $response['message'] = 'Device ID is required';
            echo json_encode($response);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO device_comments (device_id, name, email, comment, parent_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");

        if ($stmt->execute([$device_id, $name, $email, $comment, $parent_id])) {
            $response['success'] = true;
            $response['message'] = 'Thank you! Your comment has been submitted and is awaiting approval.';
        } else {
            $response['message'] = 'Failed to submit comment. Please try again.';
        }
    } elseif ($action === 'comment_post') {
        $post_id = $_POST['post_id'] ?? '';
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

        if (empty($post_id)) {
            $response['message'] = 'Post ID is required';
            echo json_encode($response);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO post_comments (post_id, name, email, comment, parent_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");

        if ($stmt->execute([$post_id, $name, $email, $comment, $parent_id])) {
            $response['success'] = true;
            $response['message'] = 'Your comment has been submitted and is pending approval.';
        } else {
            $response['message'] = 'Failed to submit comment. Please try again.';
        }
    } else {
        $response['message'] = 'Invalid action';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error. Please try again.';
    error_log('Comment submission error: ' . $e->getMessage());
}

echo json_encode($response);
