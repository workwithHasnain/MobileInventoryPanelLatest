<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_functions.php';

try {
    $pdo = getConnection();
    
    // Add columns to users table
    $sql = "ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS auth_provider VARCHAR(50) DEFAULT 'local',
            ADD COLUMN IF NOT EXISTS last_password_updated TIMESTAMP;";
            
    $pdo->exec($sql);
    
    // Update existing users to have last_password_updated as created_at if they have a password
    $sql2 = "UPDATE users SET last_password_updated = created_at WHERE last_password_updated IS NULL AND password IS NOT NULL AND password != '';";
    $pdo->exec($sql2);

    echo "Successfully updated users table with auth_provider and last_password_updated columns.";

} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
