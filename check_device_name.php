<?php

/**
 * AJAX endpoint to check if a device name + brand combination already exists.
 * Returns JSON: { exists: true/false, message: "..." }
 */

require_once __DIR__ . '/auth.php';
requireLogin();

header('Content-Type: application/json');

$name  = isset($_GET['name'])  ? trim($_GET['name'])  : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
// Optional: exclude a specific device ID (used by edit_device.php)
$excludeId = isset($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : 0;

if ($name === '') {
    echo json_encode(['exists' => false]);
    exit;
}

require_once __DIR__ . '/database_functions.php';

try {
    $pdo = getConnection();

    if ($brand !== '') {
        // Check exact name + brand match
        $sql = "SELECT id FROM phones WHERE LOWER(TRIM(name)) = LOWER(:name) AND LOWER(TRIM(brand)) = LOWER(:brand)";
        $params = [':name' => $name, ':brand' => $brand];

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->fetch()) {
            echo json_encode([
                'exists'  => true,
                'message' => 'A device named "' . htmlspecialchars($name) . '" by "' . htmlspecialchars($brand) . '" already exists.'
            ]);
            exit;
        }
    }

    // Also check name-only across all brands to warn (not block)
    $sql2 = "SELECT brand FROM phones WHERE LOWER(TRIM(name)) = LOWER(:name)";
    $params2 = [':name' => $name];

    if ($excludeId > 0) {
        $sql2 .= " AND id != :exclude_id";
        $params2[':exclude_id'] = $excludeId;
    }

    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute($params2);
    $matches = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($matches)) {
        $brandList = implode(', ', array_unique($matches));
        echo json_encode([
            'exists'  => false,
            'warning' => true,
            'message' => 'A device named "' . htmlspecialchars($name) . '" exists under: ' . htmlspecialchars($brandList) . '. Ensure the brand is different.'
        ]);
        exit;
    }

    echo json_encode(['exists' => false]);
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => 'Check failed']);
}
