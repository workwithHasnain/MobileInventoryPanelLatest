<?php

/** Database connection and utility functions for PostgreSQL */

// Load configuration
require_once 'config.php';

/**
 * Generate a URL-friendly slug from string
 *
 * @param string $text Title or name
 * @return string Safe slug
 */
function generateSlug($text)
{
    // Map common special characters to words
    $text = str_replace(['&', '+'], ['-and-', '-plus-'], $text);
    // Replace all other non-alphanumeric characters with hyphens
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    // Collapse multiple consecutive hyphens
    $text = preg_replace('/-+/', '-', $text);
    // Trim hyphens from ends and convert to lowercase
    $text = trim($text, '-');
    return strtolower($text);
}

/**
 * Get PDO connection to PostgreSQL database
 *
 * @return PDO Database connection
 * @throws Exception If connection fails
 */
function getConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            // Get database URL from environment
            $database_url = getenv('DATABASE_URL');

            if (!$database_url) {
                // Fallback to default values if DATABASE_URL is not set
                $host = getenv('PGHOST') ?: 'localhost';
                $port = getenv('PGPORT') ?: '5432';
                $dbname = getenv('PGDATABASE') ?: 'mobile_tech_hub';
                $user = getenv('PGUSER') ?: 'postgres';
                $password = getenv('PGPASSWORD') ?: 'password';

                // Create PDO connection string
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

                // Create PDO instance
                $pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                return $pdo;
            }

            // Parse the database URL
            $db_parts = parse_url($database_url);

            $host = $db_parts['host'];
            $port = $db_parts['port'] ?? 5432;
            $dbname = ltrim($db_parts['path'], '/');
            $user = $db_parts['user'];
            $password = $db_parts['pass'];

            // Create PDO connection string
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            // Create PDO instance
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw $e;
        }
    }

    return $pdo;
}

/**
 * Execute a query and return statement object
 *
 * @param string $query SQL query
 * @param array $params Query parameters
 * @return PDOStatement Statement object
 */
function executeQuery($query, $params = [])
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log('Query execution error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Execute an INSERT, UPDATE, or DELETE query
 *
 * @param string $query SQL query
 * @param array $params Query parameters
 * @return bool Success status
 */
function executeUpdate($query, $params = [])
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log('Update execution error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get the last inserted ID
 *
 * @return string|false Last insert ID or false on failure
 */
function getLastInsertId()
{
    try {
        $pdo = getConnection();
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('Get last insert ID error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Begin a database transaction
 *
 * @return bool Success status
 */
function beginTransaction()
{
    try {
        $pdo = getConnection();
        return $pdo->beginTransaction();
    } catch (Exception $e) {
        error_log('Begin transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Commit a database transaction
 *
 * @return bool Success status
 */
function commitTransaction()
{
    try {
        $pdo = getConnection();
        return $pdo->commit();
    } catch (Exception $e) {
        error_log('Commit transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rollback a database transaction
 *
 * @return bool Success status
 */
function rollbackTransaction()
{
    try {
        $pdo = getConnection();
        return $pdo->rollback();
    } catch (Exception $e) {
        error_log('Rollback transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a table exists
 *
 * @param string $table_name Table name to check
 * @return bool True if table exists, false otherwise
 */
function tableExists($table_name)
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = ?
        ");
        $stmt->execute([$table_name]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log('Table exists check error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Escape string for safe SQL usage (though prepared statements are preferred)
 *
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeString($string)
{
    try {
        $pdo = getConnection();
        return $pdo->quote($string);
    } catch (Exception $e) {
        error_log('String escape error: ' . $e->getMessage());
        return "'" . str_replace("'", "''", $string) . "'";
    }
}

/**
 * Get popular device comparisons from database
 *
 * @param int $limit Number of comparisons to return (default: 10)
 * @return array Array of popular comparisons with device details
 */
function getPopularComparisons($limit = 10)
{
    try {
        $pdo = getConnection();

        $query = "
            SELECT 
                dc.device1_id,
                dc.device2_id,
                COUNT(*) as comparison_count,
                p1.name as device1_name,
                p1.slug as device1_slug,
                p1.image as device1_image,
                b1.name as device1_brand,
                p2.name as device2_name,
                p2.slug as device2_slug,
                p2.image as device2_image,
                b2.name as device2_brand
            FROM device_comparisons dc
            LEFT JOIN phones p1 ON dc.device1_id = CAST(p1.id AS VARCHAR)
            LEFT JOIN phones p2 ON dc.device2_id = CAST(p2.id AS VARCHAR)
            LEFT JOIN brands b1 ON p1.brand_id = b1.id
            LEFT JOIN brands b2 ON p2.brand_id = b2.id
            WHERE p1.id IS NOT NULL AND p2.id IS NOT NULL
            GROUP BY dc.device1_id, dc.device2_id, p1.name, p1.slug, p1.image, b1.name, p2.name, p2.slug, p2.image, b2.name
            ORDER BY comparison_count DESC
            LIMIT ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([(int)$limit]);
        $results = $stmt->fetchAll();

        // Format results to match the expected structure
        $formattedResults = [];
        foreach ($results as $row) {
            $formattedResults[] = [
                'device1_id' => $row['device1_id'],
                'device2_id' => $row['device2_id'],
                'comparison_count' => (int)$row['comparison_count'],
                'device1_name' => $row['device1_name'],
                'device1_slug' => $row['device1_slug'] ?? '',
                'device2_name' => $row['device2_name'],
                'device2_slug' => $row['device2_slug'] ?? '',
                'device1_image' => $row['device1_image'] ?? '',
                'device2_image' => $row['device2_image'] ?? '',
                'device1_brand' => $row['device1_brand'] ?? '',
                'device2_brand' => $row['device2_brand'] ?? ''
            ];
        }

        return $formattedResults;
    } catch (Exception $e) {
        error_log('Get popular comparisons error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Brand Management Functions
 */

function getAllBrandsDB()
{
    $sql = "SELECT * FROM brands ORDER BY name ASC";
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

function getBrandByIdDB($id)
{
    $sql = "SELECT * FROM brands WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->fetch();
}

function addBrandDB($name, $description)
{
    // Check if brand already exists
    $sql = "SELECT id FROM brands WHERE LOWER(name) = LOWER(?)";
    $stmt = executeQuery($sql, [$name]);
    if ($stmt->fetch()) {
        return false; // Brand already exists
    }

    $slug = generateSlug($name);
    $sql = "INSERT INTO brands (name, slug, description) VALUES (?, ?, ?) RETURNING id";
    $stmt = executeQuery($sql, [$name, $slug, $description]);
    return $stmt->fetchColumn();
}

function updateBrandDB($id, $name, $description)
{
    // Check if another brand with the same name exists
    $sql = "SELECT id FROM brands WHERE LOWER(name) = LOWER(?) AND id != ?";
    $stmt = executeQuery($sql, [$name, $id]);
    if ($stmt->fetch()) {
        return false; // Another brand with same name exists
    }

    $slug = generateSlug($name);
    $sql = "UPDATE brands SET name = ?, slug = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = executeQuery($sql, [$name, $slug, $description, $id]);
    return $stmt->rowCount() > 0;
}

function deleteBrandDB($id)
{
    $sql = "DELETE FROM brands WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->rowCount() > 0;
}

/**
 * Chipset Management Functions
 */

function getAllChipsetsDB()
{
    $sql = "SELECT * FROM chipsets ORDER BY name ASC";
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

function getChipsetByIdDB($id)
{
    $sql = "SELECT * FROM chipsets WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->fetch();
}

function addChipsetDB($name, $description)
{
    // Check if chipset already exists
    $sql = "SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?)";
    $stmt = executeQuery($sql, [$name]);
    if ($stmt->fetch()) {
        return false; // Chipset already exists
    }

    $sql = "INSERT INTO chipsets (name, description) VALUES (?, ?) RETURNING id";
    $stmt = executeQuery($sql, [$name, $description]);
    return $stmt->fetchColumn();
}

function updateChipsetDB($id, $name, $description)
{
    // Check if another chipset with the same name exists
    $sql = "SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?) AND id != ?";
    $stmt = executeQuery($sql, [$name, $id]);
    if ($stmt->fetch()) {
        return false; // Another chipset with same name exists
    }

    $sql = "UPDATE chipsets SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = executeQuery($sql, [$name, $description, $id]);
    return $stmt->rowCount() > 0;
}

function deleteChipsetDB($id)
{
    $sql = "DELETE FROM chipsets WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->rowCount() > 0;
}

/**
 * Phone Management Functions
 */

function getAllPhonesDB()
{
    $sql = "SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            ORDER BY p.created_at DESC";
    $stmt = executeQuery($sql);
    return $stmt->fetchAll();
}

function getPhoneByIdDB($id)
{
    $sql = "SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            WHERE p.id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->fetch();
}

function getBrandIdByNameDB($brandName)
{
    $sql = "SELECT id FROM brands WHERE LOWER(name) = LOWER(?)";
    $stmt = executeQuery($sql, [$brandName]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

function getChipsetIdByNameDB($chipsetName)
{
    $sql = "SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?)";
    $stmt = executeQuery($sql, [$chipsetName]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

function addPhoneDB($phoneData)
{
    // Get brand and chipset IDs
    $brandId = getBrandIdByNameDB($phoneData['brand']);
    $chipsetId = !empty($phoneData['chipset']) ? getChipsetIdByNameDB($phoneData['chipset']) : null;

    $sql = "INSERT INTO phones (
        release_date, name, brand_id, year, availability, price, image,
        network_2g, network_3g, network_4g, network_5g,
        dual_sim, esim, sim_size,
        dimensions, form_factor, keyboard, height, width, thickness, weight, 
        ip_certificate, color, back_material, frame_material,
        os, os_version, chipset_id, cpu_cores,
        ram, storage, card_slot,
        display_type, display_resolution, display_size, display_density, 
        display_technology, display_notch, refresh_rate, hdr, billion_colors,
        main_camera_resolution, main_camera_count, main_camera_ois, main_camera_f_number,
        main_camera_telephoto, main_camera_ultrawide, main_camera_video, main_camera_flash,
        selfie_camera_resolution, selfie_camera_count, selfie_camera_ois, selfie_camera_flash,
        popup_camera, under_display_camera,
        headphone_jack, dual_speakers,
        accelerometer, gyroscope, proximity, compass, barometer,
        wifi, bluetooth, gps, nfc, infrared, usb,
        battery_capacity, battery_type, wireless_charging, fast_charging, reverse_charging
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?
    ) RETURNING id";

    $params = [
        $phoneData['release_date'] ?? null,
        $phoneData['name'],
        $brandId,
        $phoneData['year'] ?? null,
        $phoneData['availability'] ?? null,
        $phoneData['price'] ?? null,
        $phoneData['image'] ?? null,

        // Network (JSON fields)
        json_encode($phoneData['2g'] ?? []),
        json_encode($phoneData['3g'] ?? []),
        json_encode($phoneData['4g'] ?? []),
        json_encode($phoneData['5g'] ?? []),

        // SIM
        ($phoneData['dual_sim'] === true || $phoneData['dual_sim'] === 'true' || $phoneData['dual_sim'] === '1') ? true : false,
        ($phoneData['esim'] === true || $phoneData['esim'] === 'true' || $phoneData['esim'] === '1') ? true : false,
        json_encode($phoneData['sim_size'] ?? []),

        // Body
        $phoneData['dimensions'] ?? null,
        $phoneData['form_factor'] ?? null,
        $phoneData['keyboard'] ?? null,
        $phoneData['height'] ?? null,
        $phoneData['width'] ?? null,
        $phoneData['thickness'] ?? null,
        $phoneData['weight'] ?? null,
        json_encode($phoneData['ip_certificate'] ?? []),
        $phoneData['color'] ?? null,
        $phoneData['back_material'] ?? null,
        $phoneData['frame_material'] ?? null,

        // Platform
        $phoneData['os'] ?? null,
        $phoneData['os_version'] ?? null,
        $chipsetId,
        $phoneData['cpu_cores'] ?? null,

        // Memory
        $phoneData['ram'] ?? null,
        $phoneData['storage'] ?? null,
        $phoneData['card_slot'] ?? null,

        // Display
        $phoneData['display_type'] ?? null,
        $phoneData['display_resolution'] ?? null,
        $phoneData['display_size'] ?? null,
        $phoneData['display_density'] ?? null,
        $phoneData['display_technology'] ?? null,
        !empty($phoneData['display_notch']) ? true : false,
        $phoneData['refresh_rate'] ?? null,
        !empty($phoneData['hdr']) ? true : false,
        !empty($phoneData['billion_colors']) ? true : false,

        // Main Camera
        $phoneData['main_camera_resolution'] ?? null,
        $phoneData['main_camera_count'] ?? null,
        !empty($phoneData['main_camera_ois']) ? true : false,
        $phoneData['main_camera_f_number'] ?? null,
        !empty($phoneData['main_camera_telephoto']) ? true : false,
        !empty($phoneData['main_camera_ultrawide']) ? true : false,
        $phoneData['main_camera_video'] ?? null,
        $phoneData['main_camera_flash'] ?? null,

        // Selfie Camera
        $phoneData['selfie_camera_resolution'] ?? null,
        $phoneData['selfie_camera_count'] ?? null,
        !empty($phoneData['selfie_camera_ois']) ? true : false,
        !empty($phoneData['selfie_camera_flash']) ? true : false,
        !empty($phoneData['popup_camera']) ? true : false,
        !empty($phoneData['under_display_camera']) ? true : false,

        // Audio
        !empty($phoneData['headphone_jack']) ? true : false,
        !empty($phoneData['dual_speakers']) ? true : false,

        // Sensors
        !empty($phoneData['accelerometer']) ? true : false,
        !empty($phoneData['gyroscope']) ? true : false,
        !empty($phoneData['proximity']) ? true : false,
        !empty($phoneData['compass']) ? true : false,
        !empty($phoneData['barometer']) ? true : false,

        // Connectivity
        $phoneData['wifi'] ?? null,
        $phoneData['bluetooth'] ?? null,
        !empty($phoneData['gps']) ? true : false,
        !empty($phoneData['nfc']) ? true : false,
        !empty($phoneData['infrared']) ? true : false,
        $phoneData['usb'] ?? null,

        // Battery
        $phoneData['battery_capacity'] ?? null,
        $phoneData['battery_type'] ?? null,
        !empty($phoneData['wireless_charging']) ? true : false,
        !empty($phoneData['fast_charging']) ? true : false,
        !empty($phoneData['reverse_charging']) ? true : false
    ];

    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

function deletePhoneDB($id)
{
    $sql = "DELETE FROM phones WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    return $stmt->rowCount() > 0;
}

// Get top devices by daily views
function getTopDevicesByViews($limit = 10)
{
    $sql = "SELECT p.id, p.name, b.name as brand_name, p.image, p.price, p.availability, 
                   COUNT(dv.id) as daily_views
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN device_views dv ON p.id = dv.device_id AND dv.view_date = CURRENT_DATE
            GROUP BY p.id, p.name, b.name, p.image, p.price, p.availability
            ORDER BY daily_views DESC, p.name ASC
            LIMIT ?";

    $stmt = executeQuery($sql, [$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get top reviewed devices
function getTopReviewedDevices($limit = 10)
{
    $sql = "SELECT p.id, p.name, b.name as brand_name, p.image, p.price, p.availability,
                   COUNT(dr.id) as review_count,
                   ROUND(AVG(dr.rating), 1) as avg_rating
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN device_reviews dr ON p.id = dr.device_id AND dr.is_approved = true
            GROUP BY p.id, p.name, b.name, p.image, p.price, p.availability
            HAVING COUNT(dr.id) > 0
            ORDER BY review_count DESC, avg_rating DESC, p.name ASC
            LIMIT ?";

    $stmt = executeQuery($sql, [$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get top popular comparisons
function getTopPopularComparisons($limit = 10)
{
    $sql = "SELECT p1.id as device1_id, p1.name as device1_name, b1.name as device1_brand,
                   p2.id as device2_id, p2.name as device2_name, b2.name as device2_brand,
                   p1.image as device1_image, p2.image as device2_image,
                   COUNT(dc.id) as comparison_count
            FROM device_comparisons dc
            JOIN phones p1 ON dc.device1_id = p1.id
            JOIN brands b1 ON p1.brand_id = b1.id
            JOIN phones p2 ON dc.device2_id = p2.id
            JOIN brands b2 ON p2.brand_id = b2.id
            GROUP BY p1.id, p1.name, b1.name, p2.id, p2.name, b2.name, p1.image, p2.image
            ORDER BY comparison_count DESC, p1.name ASC
            LIMIT ?";

    $stmt = executeQuery($sql, [$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Track device view
function trackDeviceView($deviceId, $ipAddress)
{
    $sql = "INSERT INTO device_views (device_id, ip_address, view_date) 
            VALUES (?, ?, CURRENT_DATE) 
            ON CONFLICT (device_id, ip_address, view_date) DO NOTHING";

    return executeQuery($sql, [$deviceId, $ipAddress]);
}

// Track device comparison
function trackDeviceComparison($device1Id, $device2Id, $ipAddress)
{
    $sql = "INSERT INTO device_comparisons (device1_id, device2_id, ip_address, comparison_date) 
            VALUES (?, ?, ?, CURRENT_DATE) 
            ON CONFLICT (device1_id, device2_id, ip_address, comparison_date) DO NOTHING";

    return executeQuery($sql, [$device1Id, $device2Id, $ipAddress]);
}

