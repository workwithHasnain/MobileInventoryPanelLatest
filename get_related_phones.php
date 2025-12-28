<?php
// Get related phones - returns JSON
// Filters by price bracket (±25k), release date (within 5 years), ordered by view count
header('Content-Type: application/json');

require_once 'database_functions.php';

$device_id = isset($_GET['device_id']) ? (int)$_GET['device_id'] : 0;

if ($device_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getConnection();

    // First, get the current device details (price and release year)
    $device_stmt = $pdo->prepare("
        SELECT price, year
        FROM phones 
        WHERE id = ?
    ");
    $device_stmt->execute([$device_id]);
    $current_device = $device_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_device) {
        echo json_encode([]);
        exit;
    }

    $current_price = $current_device['price'];
    $current_year = $current_device['year'];

    // Calculate price bracket (±25000)
    $price_min = $current_price - 25000;
    $price_max = $current_price + 25000;

    // Calculate year range (within 5 years - from current year - 5 to current year)
    $current_year_actual = date('Y');
    $year_min = $current_year_actual - 5;

    // Get related phones with filters, ordered by view count
    $phones_stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.name, 
            p.brand_id, 
            p.image,
            p.price,
            p.year,
            COALESCE(dv.view_count, 0) as views
        FROM phones p
        LEFT JOIN (
            SELECT device_id, COUNT(*) as view_count
            FROM device_views
            GROUP BY device_id
        ) dv ON CAST(p.id AS VARCHAR) = dv.device_id
        WHERE 
            p.id != ?
            AND p.price IS NOT NULL
            AND p.price >= ?
            AND p.price <= ?
            AND p.year IS NOT NULL
            AND p.year >= ?
        ORDER BY dv.view_count DESC, p.year DESC
        LIMIT 10
    ");

    $phones_stmt->execute([$device_id, $price_min, $price_max, $year_min]);
    $phones = $phones_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($phones);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([]);
}
