<?php
require_once 'database_functions.php';

/**
 * Get all brands from the database
 * 
 * @return array Array of brand data
 */
function getAllBrands() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT * FROM brands ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting brands: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add a new brand to the database
 * 
 * @param array $brand Brand data
 * @return bool Success status
 */
function addBrand($brand) {
    try {
        $pdo = getConnection();
        
        // Check if brand already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM brands WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$brand['name']]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Brand already exists
        }
        
        // Insert new brand
        $stmt = $pdo->prepare("INSERT INTO brands (name, description) VALUES (?, ?)");
        return $stmt->execute([
            $brand['name'],
            $brand['description'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log('Error adding brand: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing brand
 * 
 * @param int $id Brand ID
 * @param array $brand Updated brand data
 * @return bool Success status
 */
function updateBrand($id, $brand) {
    try {
        $pdo = getConnection();
        
        // Check if new name already exists (excluding this brand)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM brands WHERE LOWER(name) = LOWER(?) AND id != ?");
        $stmt->execute([$brand['name'], $id]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Brand name already exists
        }
        
        // Update brand
        $stmt = $pdo->prepare("UPDATE brands SET name = ?, description = ? WHERE id = ?");
        return $stmt->execute([
            $brand['name'],
            $brand['description'] ?? '',
            $id
        ]);
    } catch (Exception $e) {
        error_log('Error updating brand: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a brand
 * 
 * @param int $id Brand ID
 * @return bool Success status
 */
function deleteBrand($id) {
    try {
        $pdo = getConnection();
        
        // Check if brand has devices associated with it
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE brand_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Cannot delete brand with devices
        }
        
        // Delete brand
        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log('Error deleting brand: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get brand by ID
 * 
 * @param int $id Brand ID
 * @return array|null Brand data or null if not found
 */
function getBrandById($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log('Error getting brand by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get brand by name
 * 
 * @param string $name Brand name
 * @return array|null Brand data or null if not found
 */
function getBrandByName($name) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM brands WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log('Error getting brand by name: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get all chipsets from the database
 * 
 * @return array Array of chipset data
 */
function getAllChipsets() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT * FROM chipsets ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting chipsets: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add a new chipset to the database
 * 
 * @param array $chipset Chipset data
 * @return bool Success status
 */
function addChipset($chipset) {
    try {
        $pdo = getConnection();
        
        // Check if chipset already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chipsets WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$chipset['name']]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Chipset already exists
        }
        
        // Insert new chipset
        $stmt = $pdo->prepare("INSERT INTO chipsets (name, description) VALUES (?, ?)");
        return $stmt->execute([
            $chipset['name'],
            $chipset['description'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log('Error adding chipset: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a chipset
 * 
 * @param int $id Chipset ID
 * @return bool Success status
 */
function deleteChipset($id) {
    try {
        $pdo = getConnection();
        
        // Check if chipset has devices associated with it
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE chipset_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return false; // Cannot delete chipset with devices
        }
        
        // Delete chipset
        $stmt = $pdo->prepare("DELETE FROM chipsets WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log('Error deleting chipset: ' . $e->getMessage());
        return false;
    }
}