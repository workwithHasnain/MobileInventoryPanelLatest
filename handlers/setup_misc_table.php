<?php
/**
 * setup_misc_table.php
 * Run ONCE via browser to create the misc table.
 * URL: http://localhost/MobileInventoryPanelLatest/handlers/setup_misc_table.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';

echo '<pre>';

try {
    $pdo = getConnection();

    // Create misc table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS misc (
            key   VARCHAR(100) PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        )
    ");
    echo "✅ Table 'misc' ready.\n";

    // Insert default empty key row if not exists
    $pdo->exec("
        INSERT INTO misc (key, value)
        VALUES ('extension_api_key', '')
        ON CONFLICT (key) DO NOTHING
    ");
    echo "✅ Row 'extension_api_key' ready.\n";

    echo "\nDone. You can now set the API key from the Dashboard.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo '</pre>';
