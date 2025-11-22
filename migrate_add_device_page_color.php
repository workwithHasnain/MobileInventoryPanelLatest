<?php

/**
 * Migration Script: Add device_page_color column to phones table
 * 
 * This script adds a new VARCHAR(7) column called device_page_color to the phones table.
 * The column is nullable and stores hex color codes (e.g., #ffffff).
 * 
 * Run this script once to apply the migration:
 * 1. From browser: http://localhost/MobileInventoryPanelLatest/migrate_add_device_page_color.php?token=migrate_now
 * 2. From CLI: php migrate_add_device_page_color.php
 * 
 * After successful migration, you can delete this script.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only allow execution from CLI or with a special token
if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== 'migrate_now')) {
    echo "Access denied. ";
    echo "Run from CLI or add ?token=migrate_now to the URL.\n\n";
    echo "Browser URL: http://localhost/MobileInventoryPanelLatest/migrate_add_device_page_color.php?token=migrate_now\n";
    exit(1);
}

require_once 'database_functions.php';

try {
    $pdo = getConnection();

    // Check if column already exists
    $stmt = $pdo->query("
        SELECT EXISTS(
            SELECT 1 
            FROM information_schema.columns 
            WHERE table_name = 'phones' 
            AND column_name = 'device_page_color'
        );
    ");
    $columnExists = $stmt->fetchColumn();

    if ($columnExists) {
        echo "✓ Column 'device_page_color' already exists in the 'phones' table.\n";
        exit(0);
    }

    // Add the column
    $sql = "
        ALTER TABLE phones
        ADD COLUMN device_page_color VARCHAR(7)
    ";

    $pdo->exec($sql);

    echo "✓ Successfully added 'device_page_color' column to the 'phones' table!\n";
    echo "  - Column type: VARCHAR(7)\n";
    echo "  - Nullable: Yes\n";
    echo "  - Purpose: Store hex color codes for device page theme\n";
} catch (PDOException $e) {
    echo "✗ Migration failed with database error:\n";
    echo "  " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Migration failed with error:\n";
    echo "  " . $e->getMessage() . "\n";
    exit(1);
}
