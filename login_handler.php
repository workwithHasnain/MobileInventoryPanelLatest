<?php

/**
 * AJAX Login Handler
 * Handles login requests via AJAX
 */

session_start();
require_once __DIR__ . '/database_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate input
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit();
}

try {
    // Get database connection
    $pdo = getConnection();

    // Check if admin_users table exists
    $tableCheckSQL = "
        SELECT EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_name = 'admin_users'
        )
    ";
    $stmt = $pdo->prepare($tableCheckSQL);
    $stmt->execute();
    $tableExists = $stmt->fetchColumn();

    if (!$tableExists) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Authentication system not initialized. Please run migration.']);
        exit();
    }

    // Query admin user
    $sql = "SELECT id, username, password FROM admin_users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify user and password
    if ($user && password_verify($password, $user['password'])) {
        // Authentication successful
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = $user['id'];

        echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'dashboard.php']);
    } else {
        // Authentication failed
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (Exception $e) {
    error_log('Login handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
