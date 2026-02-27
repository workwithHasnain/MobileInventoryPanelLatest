<?php

/**
 * Migration: Create public 'users' table
 * Run this script via browser to create the users table for public signup/login.
 * This is separate from admin_users which handles dashboard admin authentication.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';

echo "<h2>Migration: Create 'users' Table</h2>";

try {
    $pdo = getConnection();

    // Check if table already exists
    $check = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users')");
    $check->execute();
    $exists = $check->fetchColumn();

    if ($exists) {
        echo "<p style='color: orange;'>⚠ Table 'users' already exists. Skipping creation.</p>";
    } else {
        $sql = "
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX idx_users_email ON users(email);
            CREATE INDEX idx_users_status ON users(status);
        ";

        $pdo->exec($sql);
        echo "<p style='color: green;'>✔ Table 'users' created successfully.</p>";
    }

    // Verify table structure
    $cols = $pdo->query("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = 'users' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; font-family: monospace;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
    foreach ($cols as $col) {
        echo "<tr><td>{$col['column_name']}</td><td>{$col['data_type']}</td><td>{$col['is_nullable']}</td><td>{$col['column_default']}</td></tr>";
    }
    echo "</table>";

    echo "<p style='color: green; margin-top: 20px;'>✔ Migration complete.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✖ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
