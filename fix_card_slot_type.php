<?php
require_once 'database_functions.php';

try {
    $pdo = getConnection();
    $pdo->exec("ALTER TABLE phones ALTER COLUMN card_slot TYPE VARCHAR(10)");
    echo "✅ Column card_slot changed from BOOLEAN to VARCHAR(10)\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
