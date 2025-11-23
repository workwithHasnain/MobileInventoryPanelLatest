<?php

/**
 * Migration Script: Remove 'name' column from newsletter_subscribers table
 * Run this script from the browser to apply the migration
 */

require_once 'database_functions.php';

try {
    $pdo = getConnection();

    // Check if the 'name' column exists
    $checkColumn = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'newsletter_subscribers' 
        AND column_name = 'name'
    ");

    $columnExists = $checkColumn->fetch();

    if ($columnExists) {
        // Column exists, so drop it
        $pdo->exec("ALTER TABLE newsletter_subscribers DROP COLUMN name");
        echo "<div style='background-color: #4CAF50; color: white; padding: 20px; border-radius: 4px; margin: 20px;'>";
        echo "<h3>✓ Migration Successful</h3>";
        echo "<p>The 'name' column has been successfully removed from the newsletter_subscribers table.</p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #2196F3; color: white; padding: 20px; border-radius: 4px; margin: 20px;'>";
        echo "<h3>ℹ Info</h3>";
        echo "<p>The 'name' column does not exist in the newsletter_subscribers table. No action needed.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background-color: #f44336; color: white; padding: 20px; border-radius: 4px; margin: 20px;'>";
    echo "<h3>✗ Migration Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Newsletter Migration</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f5f5f5;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            text-align: center;
        }

        .info {
            background-color: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            border-radius: 4px;
            margin-top: 20px;
            color: #1565c0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Newsletter Database Migration</h1>
        <div class="info">
            <p><strong>Migration Purpose:</strong> Removes the 'name' field from the newsletter_subscribers table.</p>
            <p><strong>Table Affected:</strong> newsletter_subscribers</p>
            <p><strong>Action:</strong> DROP COLUMN name</p>
        </div>
    </div>
</body>

</html>