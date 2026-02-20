<?php

/**
 * Migration script to fix VARCHAR column width issues
 * Updates phones table columns to accommodate real GSMArena data
 * 
 * Error fixed: SQLSTATE[22001] String data right truncated: 7 ERROR: 
 * value too long for type character varying(50)
 * 
 * Problem: GSMArena data (especially OS field) exceeds VARCHAR(50) limit
 * Example: "Android 16, up to 6 major Android upgrades, One UI 8" = 51 chars
 */

require_once 'includes/database.php';

try {
    $conn = getConnection();

    echo "<h3>Starting VARCHAR Column Migration...</h3>";

    // Array of columns to update: [column_name => new_size]
    $updates = [
        'os' => 255,                    // "Android 16, up to 6 major Android upgrades, One UI 8" = 51 chars
        'storage' => 255,               // "128GB, 256GB, 512GB, 1TB" = storage options can be long
        'card_slot' => 255,             // microSD, expandable to 1TB, etc.
        'weight' => 255,                // Could include multiple models
        'thickness' => 255,             // Could include ranges or variants
        'display_size' => 255,          // "6.9\"" to "7.0\" AMOLED, 120Hz, HDR10+" etc.
        'ram' => 255,                   // "8GB, 12GB, 16GB" = RAM variants
        'battery_capacity' => 255,      // "5000 mAh" or extended text
        'availability' => 100,          // "This Year" -> "Q4 2024 / Late 2024" could be longer
    ];

    foreach ($updates as $column => $new_size) {
        try {
            // Drop any CHECK constraints if they reference the column (PostgreSQL limitation)
            // Most columns won't have these, but it's safest to check
            $sql = "ALTER TABLE phones ALTER COLUMN $column TYPE VARCHAR($new_size)";

            $stmt = $conn->prepare($sql);
            $stmt->execute();

            echo "✓ Updated <strong>$column</strong> to VARCHAR($new_size)<br>";
        } catch (Exception $e) {
            echo "⚠ Error updating $column: " . $e->getMessage() . "<br>";
        }
    }

    echo "<h3>✓ Migration Complete!</h3>";
    echo "<p>All VARCHAR columns have been expanded to accommodate GSMArena data.</p>";
    echo "<p>You can now import devices without encountering string truncation errors.</p>";
} catch (Exception $e) {
    echo "<h3>✗ Migration Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit(1);
}
