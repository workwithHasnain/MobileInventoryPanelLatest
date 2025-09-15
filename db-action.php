<?php
/**
 * Database Action Script - Empty All Tables
 * This script empties all table content while preserving the database structure
 */

// Include configuration
require_once 'config.php';

// Database connection using PostgreSQL
function getDatabaseConnection() {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $_ENV['PGHOST'],
        $_ENV['PGPORT'],
        $_ENV['PGDATABASE']
    );
    
    try {
        $pdo = new PDO($dsn, $_ENV['PGUSER'], $_ENV['PGPASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Empty all tables in the correct order to handle foreign key constraints
 * Order matters due to foreign key relationships
 */
function emptyAllTables() {
    $pdo = getDatabaseConnection();
    
    // Tables in deletion order (child tables first, then parent tables)
    $tables = [
        // Child tables with foreign keys first
        'post_comments',           // References posts
        'device_comments',         // References devices (varchar)
        'device_reviews',          // References devices (varchar)
        'device_views',           // References devices (varchar)
        'device_comparisons',     // References devices (varchar)
        'content_views',          // General content tracking
        'newsletter_subscribers', // No foreign keys
        
        // Tables with foreign keys to other main tables
        'phones',                 // References brands and chipsets
        'posts',                  // No foreign keys to other main tables
        'post_categories',        // No foreign keys
        
        // Core reference tables (parent tables)
        'chipsets',               // Referenced by phones
        'brands',                 // Referenced by phones
    ];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        echo "<h2>Emptying Database Tables</h2>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
        
        // Disable foreign key constraints temporarily (PostgreSQL specific)
        echo "Disabling foreign key constraints...<br>\n";
        $pdo->exec("SET session_replication_role = replica;");
        
        $totalDeleted = 0;
        
        foreach ($tables as $table) {
            try {
                // Count records before deletion
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $countStmt->fetch()['count'];
                
                if ($count > 0) {
                    // Delete all records from table
                    $deleteStmt = $pdo->prepare("DELETE FROM $table");
                    $deleteStmt->execute();
                    
                    // Reset auto-increment sequences (PostgreSQL specific)
                    $pdo->exec("SELECT setval(pg_get_serial_sequence('$table', 'id'), 1, false) WHERE EXISTS (SELECT 1 FROM pg_get_serial_sequence('$table', 'id'))");
                    
                    echo "✓ Deleted $count records from <strong>$table</strong><br>\n";
                    $totalDeleted += $count;
                } else {
                    echo "- Table <strong>$table</strong> was already empty<br>\n";
                }
                
                // Flush output for real-time feedback
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
                
            } catch (PDOException $e) {
                echo "✗ Error emptying table <strong>$table</strong>: " . $e->getMessage() . "<br>\n";
            }
        }
        
        // Re-enable foreign key constraints
        echo "<br>Re-enabling foreign key constraints...<br>\n";
        $pdo->exec("SET session_replication_role = DEFAULT;");
        
        // Commit transaction
        $pdo->commit();
        
        echo "<br><strong style='color: green;'>SUCCESS: All tables emptied successfully!</strong><br>\n";
        echo "<strong>Total records deleted: $totalDeleted</strong><br>\n";
        echo "</div>\n";
        
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        echo "<br><strong style='color: red;'>ERROR: " . $e->getMessage() . "</strong><br>\n";
        echo "Transaction rolled back.<br>\n";
        echo "</div>\n";
        return false;
    }
}

/**
 * Verify tables are empty
 */
function verifyTablesEmpty() {
    $pdo = getDatabaseConnection();
    
    $tables = [
        'brands', 'chipsets', 'posts', 'post_categories', 'phones',
        'post_comments', 'device_comments', 'device_reviews',
        'device_views', 'device_comparisons', 'newsletter_subscribers', 'content_views'
    ];
    
    echo "<h3>Verification - Table Record Counts</h3>\n";
    echo "<div style='font-family: monospace; background: #f9f9f9; padding: 15px; border-radius: 5px;'>\n";
    
    $allEmpty = true;
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            
            if ($count == 0) {
                echo "✓ <strong>$table</strong>: $count records<br>\n";
            } else {
                echo "✗ <strong>$table</strong>: $count records (NOT EMPTY)<br>\n";
                $allEmpty = false;
            }
        } catch (PDOException $e) {
            echo "? <strong>$table</strong>: Error checking - " . $e->getMessage() . "<br>\n";
            $allEmpty = false;
        }
    }
    
    echo "</div>\n";
    
    if ($allEmpty) {
        echo "<p style='color: green; font-weight: bold;'>✓ All tables are empty!</p>\n";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Some tables still contain data!</p>\n";
    }
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Action - Empty Tables</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px; 
            background-color: #f8f9fa;
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .warning { 
            background: #fff3cd; 
            border: 1px solid #ffeaa7; 
            color: #856404; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
        }
        .button {
            background: #dc3545;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .button:hover { background: #c82333; }
        .button.verify { background: #007bff; }
        .button.verify:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Management - Empty All Tables</h1>
        
        <div class="warning">
            <strong>⚠️ WARNING:</strong> This action will permanently delete ALL data from ALL tables 
            while preserving the database structure. This action cannot be undone!
        </div>
        
        <?php
        // Check if action is requested
        $action = $_GET['action'] ?? '';
        
        if ($action === 'empty') {
            echo "<h2>Executing Database Clear Operation...</h2>";
            emptyAllTables();
            echo "<br><a href='?action=verify' class='button verify'>Verify Results</a>";
            echo "<a href='?' class='button' style='background: #28a745;'>Back to Menu</a>";
        } elseif ($action === 'verify') {
            verifyTablesEmpty();
            echo "<br><a href='?' class='button' style='background: #28a745;'>Back to Menu</a>";
        } else {
            // Show menu
            echo "<h2>Available Actions</h2>";
            echo "<p>Choose an action to perform on the database:</p>";
            echo "<a href='?action=empty' class='button' onclick='return confirm(\"Are you absolutely sure you want to delete ALL data? This cannot be undone!\")'>Empty All Tables</a>";
            echo "<a href='?action=verify' class='button verify'>Verify Tables Status</a>";
            
            // Show current table status
            echo "<hr>";
            verifyTablesEmpty();
        }
        ?>
        
        <hr>
        <p><small>Database: <?php echo $_ENV['PGDATABASE']; ?> | Host: <?php echo $_ENV['PGHOST']; ?></small></p>
    </div>
</body>
</html>