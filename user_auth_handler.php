<?php

/**
 * Public User Auth Handler (AJAX)
 * Handles: register, login, logout, update_profile, delete_account
 * 
 * IMPORTANT: This is for the PUBLIC users table, completely separate from admin_users.
 * Session keys use 'public_user_*' prefix to avoid conflicts with admin sessions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/database_functions.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$action = trim($_POST['action'] ?? '');

try {
    $pdo = getConnection();

    switch ($action) {
        case 'register':
            handleRegister($pdo, $response);
            break;
        case 'login':
            handleLogin($pdo, $response);
            break;
        case 'logout':
            handleLogout($response);
            break;
        case 'update_profile':
            handleUpdateProfile($pdo, $response);
            break;
        case 'delete_account':
            handleDeleteAccount($pdo, $response);
            break;
        case 'get_profile':
            handleGetProfile($pdo, $response);
            break;
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    error_log('User auth handler error: ' . $e->getMessage());
    $response['message'] = 'An error occurred. Please try again.';
}

echo json_encode($response);
exit;

// ─── REGISTER ───
function handleRegister($pdo, &$response)
{
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $response['message'] = 'All fields are required';
        return;
    }

    if (strlen($name) < 2 || strlen($name) > 100) {
        $response['message'] = 'Name must be between 2 and 100 characters';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
        return;
    }

    if (strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters';
        return;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $response['message'] = 'An account with this email already exists';
        return;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?) RETURNING id, name, email");
    $stmt->execute([$name, $email, $hashedPassword]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Auto-login after registration
        $_SESSION['public_user_id']    = $user['id'];
        $_SESSION['public_user_name']  = $user['name'];
        $_SESSION['public_user_email'] = $user['email'];

        $response['success'] = true;
        $response['message'] = 'Account created successfully!';
        $response['user'] = ['name' => $user['name'], 'email' => $user['email']];
    } else {
        $response['message'] = 'Failed to create account. Please try again.';
    }
}

// ─── LOGIN ───
function handleLogin($pdo, &$response)
{
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $response['message'] = 'Email and password are required';
        return;
    }

    $stmt = $pdo->prepare("SELECT id, name, email, password, status FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $response['message'] = 'Invalid email or password';
        return;
    }

    if ($user['status'] !== 'active') {
        $response['message'] = 'Your account has been deactivated. Please contact support.';
        return;
    }

    $_SESSION['public_user_id']    = $user['id'];
    $_SESSION['public_user_name']  = $user['name'];
    $_SESSION['public_user_email'] = $user['email'];

    $response['success'] = true;
    $response['message'] = 'Login successful!';
    $response['user'] = ['name' => $user['name'], 'email' => $user['email']];
}

// ─── LOGOUT ───
function handleLogout(&$response)
{
    unset($_SESSION['public_user_id'], $_SESSION['public_user_name'], $_SESSION['public_user_email']);
    $response['success'] = true;
    $response['message'] = 'Logged out successfully';
}

// ─── GET PROFILE ───
function handleGetProfile($pdo, &$response)
{
    if (empty($_SESSION['public_user_id'])) {
        $response['message'] = 'Not logged in';
        return;
    }

    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['public_user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $response['success'] = true;
        $response['user'] = $user;
    } else {
        $response['message'] = 'User not found';
    }
}

// ─── UPDATE PROFILE ───
function handleUpdateProfile($pdo, &$response)
{
    if (empty($_SESSION['public_user_id'])) {
        $response['message'] = 'Not logged in';
        return;
    }

    $userId = $_SESSION['public_user_id'];
    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword    = trim($_POST['new_password'] ?? '');

    if (empty($name) || empty($email)) {
        $response['message'] = 'Name and email are required';
        return;
    }

    if (strlen($name) < 2 || strlen($name) > 100) {
        $response['message'] = 'Name must be between 2 and 100 characters';
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address';
        return;
    }

    // Check email uniqueness (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        $response['message'] = 'This email is already in use by another account';
        return;
    }

    // If changing password, verify current password
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $response['message'] = 'Current password is required to set a new password';
            return;
        }
        if (strlen($newPassword) < 6) {
            $response['message'] = 'New password must be at least 6 characters';
            return;
        }

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            $response['message'] = 'Current password is incorrect';
            return;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $hashedPassword, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $userId]);
    }

    // Update session
    $_SESSION['public_user_name']  = $name;
    $_SESSION['public_user_email'] = $email;

    $response['success'] = true;
    $response['message'] = 'Profile updated successfully!';
    $response['user'] = ['name' => $name, 'email' => $email];
}

// ─── DELETE ACCOUNT ───
function handleDeleteAccount($pdo, &$response)
{
    if (empty($_SESSION['public_user_id'])) {
        $response['message'] = 'Not logged in';
        return;
    }

    $userId  = $_SESSION['public_user_id'];
    $password = trim($_POST['password'] ?? '');

    if (empty($password)) {
        $response['message'] = 'Please enter your password to confirm account deletion';
        return;
    }

    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password'])) {
        $response['message'] = 'Incorrect password';
        return;
    }

    // Soft-delete: set status to 'deleted'
    $stmt = $pdo->prepare("UPDATE users SET status = 'deleted', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);

    // Clear session
    unset($_SESSION['public_user_id'], $_SESSION['public_user_name'], $_SESSION['public_user_email']);

    $response['success'] = true;
    $response['message'] = 'Your account has been deleted.';
}
