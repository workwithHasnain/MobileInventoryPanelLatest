<?php

/**
 * Migration Script: Convert VARCHAR Columns to TEXT in Phones Table
 * 
 * This script converts all VARCHAR columns in the phones table to TEXT.
 * TEXT columns have no character limit, providing more flexibility for data.
 * 
 * VARCHAR columns being converted:
 * - brand, name, device_page_color, image, weight, thickness
 * - os, storage, card_slot, display_size, display_resolution
 * - main_camera_resolution, main_camera_video, ram, chipset_name
 * - battery_capacity, wired_charging, wireless_charging, slug, meta_title, availability
 * 
 * Run this script once from your browser to update the database.
 * URL: http://localhost/MobileInventoryPanelLatest/migrate_varchar_to_text.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'database_functions.php';

// Check if script has already been run
$migration_marker = __DIR__ . '/.migration_varchar_to_text_done';
if (file_exists($migration_marker)) {
    die('<h2 style="color: orange;">⚠️ Migration Already Completed</h2><p>This migration has already been run. Delete the file <code>.migration_varchar_to_text_done</code> if you need to run it again.</p>');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - VARCHAR to TEXT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }

        h2 {
            color: #555;
            margin-top: 20px;
        }

        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .step {
            margin: 10px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }

        code {
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        .column-list {
            columns: 2;
            gap: 20px;
            margin: 10px 0;
        }

        .column-item {
            break-inside: avoid;
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔄 Phones Table: VARCHAR to TEXT Conversion</h1>
        <div class="info">
            <strong>Purpose:</strong> Convert all VARCHAR columns to TEXT to remove character limits and improve data flexibility.
        </div>

        <h2>Columns to Convert:</h2>
        <div class="column-list">
            <div class="column-item">✓ brand</div>
            <div class="column-item">✓ name</div>
            <div class="column-item">✓ device_page_color</div>
            <div class="column-item">✓ image</div>
            <div class="column-item">✓ weight</div>
            <div class="column-item">✓ thickness</div>
            <div class="column-item">✓ os</div>
            <div class="column-item">✓ storage</div>
            <div class="column-item">✓ card_slot</div>
            <div class="column-item">✓ display_size</div>
            <div class="column-item">✓ display_resolution</div>
            <div class="column-item">✓ main_camera_resolution</div>
            <div class="column-item">✓ main_camera_video</div>
            <div class="column-item">✓ ram</div>
            <div class="column-item">✓ chipset_name</div>
            <div class="column-item">✓ battery_capacity</div>
            <div class="column-item">✓ wired_charging</div>
            <div class="column-item">✓ wireless_charging</div>
            <div class="column-item">✓ slug</div>
            <div class="column-item">✓ meta_title</div>
            <div class="column-item">✓ availability</div>
        </div>

        <?php
        try {
            $conn = getConnection();

            // List of VARCHAR columns to convert to TEXT
            $columns = [
                'brand',
                'name',
                'device_page_color',
                'image',
                'weight',
                'thickness',
                'os',
                'storage',
                'card_slot',
                'display_size',
                'display_resolution',
                'main_camera_resolution',
                'main_camera_video',
                'ram',
                'chipset_name',
                'battery_capacity',
                'wired_charging',
                'wireless_charging',
                'slug',
                'meta_title',
                'availability'
            ];

            echo "<h2>Migration Progress:</h2>";
            $success_count = 0;
            $error_count = 0;

            foreach ($columns as $column) {
                try {
                    // PostgreSQL: ALTER COLUMN TYPE to TEXT
                    $sql = "ALTER TABLE phones ALTER COLUMN {$column} TYPE TEXT";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute();

                    echo "<div class='step'>✓ Converted <strong>{$column}</strong> to TEXT</div>";
                    $success_count++;
                } catch (Exception $e) {
                    echo "<div class='step' style='border-left-color: #ff6b6b;'>⚠ Error converting {$column}: " . htmlspecialchars($e->getMessage()) . "</div>";
                    $error_count++;
                }
            }

            // Summary
            echo "<h2>Migration Summary:</h2>";
            if ($error_count === 0) {
                echo "<div class='success'>";
                echo "<strong>✓ Migration Completed Successfully!</strong><br>";
                echo "Converted <strong>{$success_count}</strong> columns from VARCHAR to TEXT.<br>";
                echo "All text fields in the phones table now support unlimited character length.";
                echo "</div>";

                // Create marker file to prevent re-running
                file_put_contents($migration_marker, date('Y-m-d H:i:s'));
            } else {
                echo "<div class='error'>";
                echo "<strong>✗ Migration Partially Failed</strong><br>";
                echo "Successfully converted: <strong>{$success_count}</strong> columns<br>";
                echo "Failed conversions: <strong>{$error_count}</strong> columns<br>";
                echo "Please check the errors above and retry.";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>✗ Migration Failed</strong><br>";
            echo "Database Error: " . htmlspecialchars($e->getMessage());
            echo "</div>";
            exit(1);
        }
        ?>

    </div>
</body>

</html>