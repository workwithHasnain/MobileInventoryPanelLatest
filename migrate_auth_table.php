<?php

/**
 * Database migration script for authentication table
 * Run this from browser: http://localhost/MobileInventoryPanelLatest/migrate_auth_table.php
 */

require_once __DIR__ . '/database_functions.php';

try {
    $pdo = getConnection();

    // Create admin_users table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS admin_users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    ";

    $pdo->exec($createTableSQL);

    // Create index on username
    $createIndexSQL = "CREATE INDEX IF NOT EXISTS idx_admin_users_username ON admin_users(username)";
    $pdo->exec($createIndexSQL);

    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    $adminExists = $stmt->fetch();

    if (!$adminExists) {
        // Insert default admin user with password hashing
        $hashedPassword = password_hash('1234', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hashedPassword]);
        $result = "✓ Database migration completed successfully!<br>✓ admin_users table created<br>✓ Default admin user created with username 'admin' and password '1234'";
    } else {
        $result = "✓ Database migration completed successfully!<br>✓ admin_users table already exists<br>✓ Default admin user already exists";
    }

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Migration</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body { padding: 20px; }
            .container { max-width: 600px; margin-top: 50px; }
            .alert { margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Database Migration</h1>
            <div class='alert alert-success'>
                <strong>Success!</strong><br>
                $result
            </div>
            <a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a>
        </div>
    </body>
    </html>";
} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Migration Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body { padding: 20px; }
            .container { max-width: 600px; margin-top: 50px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Database Migration Error</h1>
            <div class='alert alert-danger'>
                <strong>Error:</strong><br>
                " . htmlspecialchars($e->getMessage()) . "
            </div>
            <a href='javascript:history.back()' class='btn btn-secondary'>Go Back</a>
        </div>
    </body>
    </html>";
    error_log('Migration error: ' . $e->getMessage());
}
