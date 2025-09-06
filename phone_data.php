<?php
require_once 'database_functions.php';

/**
 * Get all phones from the database
 * 
 * @return array Array of phone data
 */
function getAllPhones() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("
            SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            ORDER BY p.name
        ");
        $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format phones to match expected structure
        foreach ($phones as &$phone) {
            $phone['brand'] = $phone['brand_name'];
            $phone['chipset'] = $phone['chipset_name'];
        }
        
        return $phones;
    } catch (Exception $e) {
        error_log('Error getting phones: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add a new phone to the database
 * 
 * @param array $phone Phone data
 * @return bool Success status
 */
function addPhone($phone) {
    try {
        $pdo = getConnection();
        
        // Get brand ID
        $brand_id = null;
        if (isset($phone['brand'])) {
            $stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$phone['brand']]);
            $brand_id = $stmt->fetchColumn();
        }
        
        // Get chipset ID
        $chipset_id = null;
        if (isset($phone['chipset'])) {
            $stmt = $pdo->prepare("SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$phone['chipset']]);
            $chipset_id = $stmt->fetchColumn();
        }
        
        // Check for exact duplicate device (same name and brand only)
        if ($brand_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE LOWER(name) = LOWER(?) AND brand_id = ?");
            $stmt->execute([trim($phone['name']), $brand_id]);
            if ($stmt->fetchColumn() > 0) {
                return false; // Duplicate found
            }
        }
        
        // Insert only essential fields to avoid parameter mismatch
        $stmt = $pdo->prepare("
            INSERT INTO phones (name, brand_id, availability, price, year, image) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $phone['name'] ?? '',
            $brand_id,
            $phone['availability'] ?? '',
            $phone['price'] ?? null,
            $phone['year'] ?? null,
            is_array($phone['image'] ?? '') ? (isset($phone['image'][0]) ? $phone['image'][0] : '') : ($phone['image'] ?? '')
        ]);
    } catch (Exception $e) {
        error_log('Error adding phone: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing phone
 * 
 * @param int $id Phone ID
 * @param array $phone Updated phone data
 * @return bool Success status
 */
function updatePhone($id, $phone) {
    try {
        $pdo = getConnection();
        
        // Get brand ID
        $brand_id = null;
        if (isset($phone['brand'])) {
            $stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$phone['brand']]);
            $brand_id = $stmt->fetchColumn();
        }
        
        // Get chipset ID
        $chipset_id = null;
        if (isset($phone['chipset'])) {
            $stmt = $pdo->prepare("SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$phone['chipset']]);
            $chipset_id = $stmt->fetchColumn();
        }
        
        // Update phone
        $stmt = $pdo->prepare("
            UPDATE phones SET 
                name = ?, brand_id = ?, availability = ?, price = ?, year = ?,
                network_5g = ?, network_4g = ?, network_3g = ?, network_2g = ?,
                dual_sim = ?, esim = ?,
                dimensions = ?, weight = ?, color = ?,
                os = ?, chipset_id = ?, cpu_cores = ?,
                ram = ?, storage = ?,
                display_type = ?, display_size = ?, display_resolution = ?, refresh_rate = ?,
                main_camera_resolution = ?, selfie_camera_resolution = ?,
                dual_speakers = ?, headphone_jack = ?,
                accelerometer = ?, gyroscope = ?, proximity = ?, compass = ?,
                wifi = ?, bluetooth = ?, gps = ?, nfc = ?,
                battery_capacity = ?, wireless_charging = ?, fast_charging = ?,
                image = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $phone['name'] ?? '',
            $brand_id,
            $phone['availability'] ?? '',
            $phone['price'] ?? null,
            $phone['year'] ?? null,
            isset($phone['network_5g']) ? json_encode($phone['network_5g']) : '{}',
            isset($phone['network_4g']) ? json_encode($phone['network_4g']) : '{}',
            isset($phone['network_3g']) ? json_encode($phone['network_3g']) : '{}',
            isset($phone['network_2g']) ? json_encode($phone['network_2g']) : '{}',
            isset($phone['dual_sim']) ? (bool)$phone['dual_sim'] : false,
            isset($phone['esim']) ? (bool)$phone['esim'] : false,
            $phone['dimensions'] ?? '',
            $phone['weight'] ?? null,
            $phone['color'] ?? '',
            $phone['os'] ?? '',
            $chipset_id,
            $phone['cpu_cores'] ?? '',
            $phone['ram'] ?? '',
            $phone['storage'] ?? '',
            $phone['display_type'] ?? '',
            $phone['display_size'] ?? '',
            $phone['display_resolution'] ?? '',
            $phone['refresh_rate'] ?? '',
            $phone['main_camera_resolution'] ?? '',
            $phone['selfie_camera_resolution'] ?? '',
            isset($phone['dual_speakers']) ? (bool)$phone['dual_speakers'] : false,
            isset($phone['headphone_jack']) ? (bool)$phone['headphone_jack'] : false,
            isset($phone['accelerometer']) ? (bool)$phone['accelerometer'] : false,
            isset($phone['gyroscope']) ? (bool)$phone['gyroscope'] : false,
            isset($phone['proximity']) ? (bool)$phone['proximity'] : false,
            isset($phone['compass']) ? (bool)$phone['compass'] : false,
            $phone['wifi'] ?? '',
            $phone['bluetooth'] ?? '',
            isset($phone['gps']) ? (bool)$phone['gps'] : false,
            isset($phone['nfc']) ? (bool)$phone['nfc'] : false,
            $phone['battery_capacity'] ?? '',
            isset($phone['wireless_charging']) ? (bool)$phone['wireless_charging'] : false,
            isset($phone['fast_charging']) ? (bool)$phone['fast_charging'] : false,
            $phone['image'] ?? '',
            $id
        ]);
    } catch (Exception $e) {
        error_log('Error updating phone: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a phone
 * 
 * @param int $id Phone ID
 * @return bool Success status
 */
function deletePhone($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM phones WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log('Error deleting phone: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get phone by ID
 * 
 * @param int $id Phone ID
 * @return array|null Phone data or null if not found
 */
function getPhoneById($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $phone = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($phone) {
            $phone['brand'] = $phone['brand_name'];
            $phone['chipset'] = $phone['chipset_name'];
        }
        
        return $phone ?: null;
    } catch (Exception $e) {
        error_log('Error getting phone by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Generate a unique device ID from name and brand (legacy compatibility)
 * 
 * @param string $name Device name
 * @param string $brand Device brand
 * @return string Unique device ID
 */
function generateDeviceId($name, $brand) {
    // This function is kept for backward compatibility
    // In database mode, IDs are auto-generated
    return uniqid($brand . '_' . $name . '_');
}