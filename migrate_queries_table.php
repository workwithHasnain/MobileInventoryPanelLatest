<?php
/**
 * Migration: Update queries table - remove DEFAULT, set NOT NULL on query_type
 * Run from browser: http://localhost/MobileInventoryPanelLatest/migrate_queries_table.php
 * 
 * Safe to run multiple times - handles both fresh installs and existing tables.
 */

require_once 'database_functions.php';

try {
    $pdo = getConnection();

    // Check if the table exists
    $check = $pdo->query("SELECT to_regclass('public.queries')");
    $exists = $check->fetchColumn();

    if (!$exists) {
        // Table doesn't exist yet - create it fresh
        $pdo->exec("
            CREATE TABLE queries (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                query TEXT NOT NULL,
                query_type VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_queries_email ON queries(email);
            CREATE INDEX IF NOT EXISTS idx_queries_query_type ON queries(query_type);
            CREATE INDEX IF NOT EXISTS idx_queries_created_at ON queries(created_at);
        ");
        echo "<h2 style='color: green;'>&#10004; Table created</h2>";
        echo "<p>The <strong>queries</strong> table was created with <code>query_type NOT NULL</code>.</p>";
    } else {
        // Table exists - update any rows with NULL query_type, then alter column
        $updated = $pdo->exec("UPDATE queries SET query_type = 'contact' WHERE query_type IS NULL");
        $pdo->exec("ALTER TABLE queries ALTER COLUMN query_type SET NOT NULL");
        $pdo->exec("ALTER TABLE queries ALTER COLUMN query_type DROP DEFAULT");

        echo "<h2 style='color: green;'>&#10004; Migration complete</h2>";
        echo "<p>Column <code>query_type</code> is now <strong>NOT NULL</strong> with no default.</p>";
        if ($updated > 0) {
            echo "<p>Updated <strong>$updated</strong> existing row(s) from NULL to 'contact'.</p>";
        }
    }

    echo "<br><p><a href='contact-us'>Contact Us</a> | <a href='advertise-with-us'>Advertise With Us</a></p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>&#10008; Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
