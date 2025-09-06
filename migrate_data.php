<?php
/**
 * Migration script to move JSON data to PostgreSQL database
 */

require_once 'includes/database_functions.php';
require_once 'brand_data.php';

echo "Starting data migration from JSON to PostgreSQL...\n\n";

try {
    // Migrate existing JSON brands to database (skip duplicates)
    echo "Migrating brands...\n";
    $jsonBrands = getAllBrands(); // From JSON
    $dbBrands = getAllBrandsDB(); // From database
    
    // Get existing brand names to avoid duplicates
    $existingBrands = array_column($dbBrands, 'name');
    
    $brandsMigrated = 0;
    foreach ($jsonBrands as $brand) {
        if (!in_array($brand['name'], $existingBrands)) {
            $result = addBrandDB($brand['name'], $brand['description'] ?? '');
            if ($result) {
                echo "✓ Migrated brand: {$brand['name']}\n";
                $brandsMigrated++;
            } else {
                echo "✗ Failed to migrate brand: {$brand['name']}\n";
            }
        } else {
            echo "- Skipped existing brand: {$brand['name']}\n";
        }
    }
    
    // Migrate existing JSON chipsets to database (skip duplicates)
    echo "\nMigrating chipsets...\n";
    $jsonChipsets = getAllChipsets(); // From JSON
    $dbChipsets = getAllChipsetsDB(); // From database
    
    // Get existing chipset names to avoid duplicates
    $existingChipsets = array_column($dbChipsets, 'name');
    
    $chipsetsMigrated = 0;
    foreach ($jsonChipsets as $chipset) {
        if (!in_array($chipset['name'], $existingChipsets)) {
            $result = addChipsetDB($chipset['name'], $chipset['description'] ?? '');
            if ($result) {
                echo "✓ Migrated chipset: {$chipset['name']}\n";
                $chipsetsMigrated++;
            } else {
                echo "✗ Failed to migrate chipset: {$chipset['name']}\n";
            }
        } else {
            echo "- Skipped existing chipset: {$chipset['name']}\n";
        }
    }
    
    // Migrate phones from JSON
    echo "\nMigrating phones...\n";
    if (file_exists('data/phones.json')) {
        $jsonData = file_get_contents('data/phones.json');
        $phones = json_decode($jsonData, true) ?: [];
        
        $phonesMigrated = 0;
        foreach ($phones as $phone) {
            try {
                $result = addPhoneDB($phone);
                if ($result) {
                    echo "✓ Migrated phone: {$phone['name']}\n";
                    $phonesMigrated++;
                } else {
                    echo "✗ Failed to migrate phone: {$phone['name']}\n";
                }
            } catch (Exception $e) {
                echo "✗ Error migrating phone {$phone['name']}: " . $e->getMessage() . "\n";
            }
        }
        echo "Migrated $phonesMigrated phones.\n";
    } else {
        echo "No phones.json file found.\n";
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Brands migrated: $brandsMigrated\n";
    echo "Chipsets migrated: $chipsetsMigrated\n";
    echo "Phones migrated: " . ($phonesMigrated ?? 0) . "\n";
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>