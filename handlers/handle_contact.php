<?php

/**
 * Contact Form Handler - AJAX endpoint
 * Handles contact form submissions with spam link validation
 */

header('Content-Type: application/json');
require_once 'database_functions.php';

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$name = trim($_POST['contact_name'] ?? '');
$email = trim($_POST['contact_email'] ?? '');
$query = trim($_POST['contact_query'] ?? '');
$queryType = trim($_POST['query_type'] ?? 'contact');

// Validate query_type (whitelist allowed values)
$allowedTypes = ['contact', 'ad'];
if (!in_array($queryType, $allowedTypes)) {
    $queryType = 'contact';
}

// Validate required fields
if (empty($name)) {
    $response['message'] = 'Name is required.';
    echo json_encode($response);
    exit;
}

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

if (empty($query)) {
    $response['message'] = 'Query/message is required.';
    echo json_encode($response);
    exit;
}

if (strlen($query) < 10) {
    $response['message'] = 'Your message is too short. Please provide more details.';
    echo json_encode($response);
    exit;
}

if (strlen($query) > 5000) {
    $response['message'] = 'Your message is too long. Please keep it under 5000 characters.';
    echo json_encode($response);
    exit;
}

if (strlen($name) > 100) {
    $response['message'] = 'Name must be under 100 characters.';
    echo json_encode($response);
    exit;
}

// --- Spam link detection ---
// Check for URLs/links in the query text
$urlPatterns = [
    '/https?:\/\/[^\s]+/i',                           // http/https links
    '/www\.[^\s]+/i',                                  // www. links
    '/[a-zA-Z0-9.-]+\.(com|net|org|info|biz|xyz|ru|cn|tk|ml|ga|cf|gq|top|work|click|link|site|online|store|shop|buzz|pw|cc|io|co|me)\b/i', // domain patterns
    '/\[url[=\]].*?\[\/url\]/i',                       // BBCode links
    '/<a\s[^>]*href[^>]*>/i',                          // HTML links
    '/href\s*=\s*["\'][^"\']*["\']/i',                 // href attributes
];

foreach ($urlPatterns as $pattern) {
    if (preg_match($pattern, $query)) {
        $response['message'] = 'Links/URLs are not allowed in the message. Please remove any links and try again.';
        echo json_encode($response);
        exit;
    }
}

// Check for excessive repetition (spam indicator)
if (preg_match('/(.{3,})\1{3,}/', $query)) {
    $response['message'] = 'Your message appears to contain spam. Please try again.';
    echo json_encode($response);
    exit;
}

// Check for name containing links
foreach ($urlPatterns as $pattern) {
    if (preg_match($pattern, $name)) {
        $response['message'] = 'Links/URLs are not allowed in the name field.';
        echo json_encode($response);
        exit;
    }
}

// --- End spam detection ---

try {
    $pdo = getConnection();

    $stmt = $pdo->prepare("
        INSERT INTO queries (name, email, query, query_type, created_at)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$name, $email, $query, $queryType]);

    $response['success'] = true;
    $response['message'] = 'Thank you for reaching out! We will get back to you within 24-48 hours.';
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    error_log('Contact form trace: ' . $e->getTraceAsString());
    $response['message'] = 'An error occurred while submitting your message. Please try again later.';
}

echo json_encode($response);
