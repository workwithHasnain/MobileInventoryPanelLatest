<?php

/**
 * Setup script for the queries table (Contact Form)
 * Run this from the browser to create the table in PostgreSQL.
 * URL: http://localhost/MobileInventoryPanelLatest/setup_contact_table.php
 */

require_once 'database_functions.php';

try {
    $pdo = getConnection();

    $sql = "
        CREATE TABLE IF NOT EXISTS queries (
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
    ";

    $pdo->exec($sql);

    echo "<h2 style='color: green;'>&#10004; Success!</h2>";
    echo "<p>The <strong>queries</strong> table has been created successfully.</p>";
    echo "<p><a href='contact-us'>Go to Contact Us page</a></p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>&#10008; Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
