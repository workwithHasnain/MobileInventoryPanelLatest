<?php
/**
 * AJAX Auth Management Handler
 * Handles user listing, creation, update, and deletion
 */

session_start();
require_once __DIR__ . '/database_functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
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

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

try {
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
        echo json_encode(['success' => false, 'message' => 'Authentication table not found']);
        exit();
    }
    
    switch ($action) {
        case 'list':
            handleListUsers($pdo);
            break;
        case 'add':
            handleAddUser($pdo);
            break;
        case 'update':
            handleUpdateUser($pdo);
            break;
        case 'delete':
            handleDeleteUser($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log('Auth handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

function handleListUsers($pdo) {
    $sql = "SELECT id, username, password, created_at FROM admin_users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);
}

function handleAddUser($pdo) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit();
    }
    
    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
        exit();
    }
    
    if (strlen($password) < 4) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters']);
        exit();
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE LOWER(username) = LOWER(?)");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $sql = "INSERT INTO admin_users (username, password) VALUES (?, ?) RETURNING id, username, password, created_at";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $hashedPassword]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'message' => 'User added successfully', 'user' => $user]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
}

function handleUpdateUser($pdo) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (!$id || empty($username)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or username']);
        exit();
    }
    
    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
        exit();
    }
    
    // Check if username is taken by another user
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE LOWER(username) = LOWER(?) AND id != ?");
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // If password is provided, update both username and password
    if (!empty($password)) {
        if (strlen($password) < 4) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters']);
            exit();
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE admin_users SET username = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? RETURNING id, username, password, created_at";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $hashedPassword, $id]);
    } else {
        // Update only username
        $sql = "UPDATE admin_users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? RETURNING id, username, password, created_at";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $id]);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully', 'user' => $user]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
}

function handleDeleteUser($pdo) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit();
    }
    
    // Prevent deleting the last admin user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count <= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin user']);
        exit();
    }
    
    $sql = "DELETE FROM admin_users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
}
?>
