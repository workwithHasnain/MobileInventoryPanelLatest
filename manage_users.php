<?php

/**
 * Admin handler for managing PUBLIC users (the 'users' table, NOT admin_users).
 * Used by dashboard.php to list, add, update, and delete public user accounts.
 * Requires admin login to access.
 */

session_start();
require_once __DIR__ . '/database_functions.php';

header('Content-Type: application/json');

// Admin auth check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = trim($_POST['action'] ?? '');

try {
    $pdo = getConnection();

    switch ($action) {
        case 'list':
            $stmt = $pdo->prepare("SELECT id, name, email, status, created_at, updated_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'add':
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Name, email, and password are required']);
                exit();
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit();
            }
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit();
            }

            // Check duplicate email
            $chk = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already registered']);
                exit();
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?) RETURNING id, name, email, status, created_at");
            $stmt->execute([$name, $email, $hash]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'message' => 'User created successfully', 'user' => $user]);
            break;

        case 'update':
            $id       = intval($_POST['id'] ?? 0);
            $name     = trim($_POST['name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $status   = trim($_POST['status'] ?? '');

            if (!$id || empty($name) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'ID, name, and email are required']);
                exit();
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit();
            }

            // Check email uniqueness (excluding this user)
            $chk = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
            $chk->execute([$email, $id]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already in use by another user']);
                exit();
            }

            if (!empty($password)) {
                if (strlen($password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                    exit();
                }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $hash, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $email, $status, $id]);
            }

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Manage users error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
