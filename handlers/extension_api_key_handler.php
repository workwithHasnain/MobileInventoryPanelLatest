<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';

requireLogin();

header('Content-Type: application/json');

// Admin only
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin only']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getConnection();

    if ($action === 'get') {
        $stmt = $pdo->prepare("SELECT value FROM misc WHERE key = 'extension_api_key'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'key' => $row['value'] ?? '']);

    } elseif ($action === 'save') {
        $newKey = trim($_POST['api_key'] ?? '');
        if (strlen($newKey) < 6) {
            echo json_encode(['success' => false, 'error' => 'Key must be at least 6 characters']);
            exit;
        }
        $stmt = $pdo->prepare("
            INSERT INTO misc (key, value) VALUES ('extension_api_key', ?)
            ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value
        ");
        $stmt->execute([$newKey]);
        echo json_encode(['success' => true, 'message' => 'API key saved']);

    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
