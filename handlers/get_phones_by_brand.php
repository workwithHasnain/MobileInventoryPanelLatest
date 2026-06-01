<?php
// Get phones by brand - returns JSON
header('Content-Type: application/json');

require_once 'database_functions.php';

$brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;

if ($brand_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getConnection();

    // Get phones for the selected brand
    $stmt = $pdo->prepare("
        SELECT id, name, brand_id, image, slug
        FROM phones 
        WHERE brand_id = ? 
        ORDER BY name ASC
    ");
    $stmt->execute([$brand_id]);
    $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($phones);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([]);
}
