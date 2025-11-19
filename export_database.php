<?php

/**
 * Database Export Script
 * Exports all data from database tables into an SQL file and downloads it
 */

// Include database functions
require_once __DIR__ . '/database_functions.php';

// Set execution time limit to avoid timeout for large databases
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '256M');

try {
    // Get database connection
    $pdo = getConnection();

    // Get database name
    $dbname = getenv('PGDATABASE') ?: 'mobile_tech_hub';

    // Start building SQL content
    $sqlContent = "-- Database Export\n";
    $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Database: {$dbname}\n\n";
    $sqlContent .= "SET client_encoding = 'UTF8';\n";
    $sqlContent .= "SET standard_conforming_strings = on;\n\n";

    // Get all tables from the database
    $query = "SELECT table_name 
              FROM information_schema.tables 
              WHERE table_schema = 'public' 
              AND table_type = 'BASE TABLE'
              ORDER BY table_name";

    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        throw new Exception("No tables found in the database.");
    }

    // Loop through each table and export data
    foreach ($tables as $table) {
        $sqlContent .= "\n-- --------------------------------------------------------\n";
        $sqlContent .= "-- Table: {$table}\n";
        $sqlContent .= "-- --------------------------------------------------------\n\n";

        // Get table structure (for reference in comments)
        $columnsQuery = "SELECT column_name, data_type, is_nullable, column_default
                         FROM information_schema.columns
                         WHERE table_name = :table
                         AND table_schema = 'public'
                         ORDER BY ordinal_position";

        $columnsStmt = $pdo->prepare($columnsQuery);
        $columnsStmt->execute([':table' => $table]);
        $columns = $columnsStmt->fetchAll();

        // Add column information as comments
        $sqlContent .= "-- Columns:\n";
        foreach ($columns as $column) {
            $nullable = $column['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $column['column_default'] ? " DEFAULT {$column['column_default']}" : '';
            $sqlContent .= "-- - {$column['column_name']} ({$column['data_type']}) {$nullable}{$default}\n";
        }
        $sqlContent .= "\n";

        // Get row count
        $countStmt = $pdo->query("SELECT COUNT(*) FROM \"{$table}\"");
        $rowCount = $countStmt->fetchColumn();

        if ($rowCount > 0) {
            // Get all data from the table
            $dataStmt = $pdo->query("SELECT * FROM \"{$table}\"");
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get column names
            $columnNames = array_keys($rows[0]);
            $escapedColumns = array_map(function ($col) {
                return '"' . $col . '"';
            }, $columnNames);

            $sqlContent .= "-- Dumping data for table {$table}\n";
            $sqlContent .= "-- {$rowCount} rows\n\n";

            // Generate INSERT statements
            foreach ($rows as $row) {
                $values = [];

                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $values[] = $value ? 'TRUE' : 'FALSE';
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } elseif (is_array($value) || is_object($value)) {
                        // Handle array/JSON data
                        $escapedValue = str_replace("'", "''", json_encode($value));
                        $values[] = "'" . $escapedValue . "'";
                    } else {
                        // Escape string values
                        $escapedValue = str_replace("'", "''", $value);
                        $values[] = "'" . $escapedValue . "'";
                    }
                }

                $sqlContent .= "INSERT INTO \"{$table}\" (" . implode(', ', $escapedColumns) . ") VALUES (";
                $sqlContent .= implode(', ', $values);
                $sqlContent .= ");\n";
            }

            $sqlContent .= "\n";
        } else {
            $sqlContent .= "-- No data in table {$table}\n\n";
        }
    }

    // Add footer
    $sqlContent .= "\n-- Export completed successfully\n";
    $sqlContent .= "-- Total tables exported: " . count($tables) . "\n";

    // Generate filename with timestamp
    $filename = $dbname . '_export_' . date('Y-m-d_His') . '.sql';

    // Set headers for download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sqlContent));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Past date

    // Output the SQL content
    echo $sqlContent;

    // Exit to prevent any additional output
    exit;
} catch (Exception $e) {
    // Log error
    error_log('Database export error: ' . $e->getMessage());

    // Display error to user
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Database Export Error</title>\n";
    echo "<style>body { font-family: system ui, sans-serif; padding: 20px; }\n";
    echo ".error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; border-left: 4px solid #c62828; }\n";
    echo "h1 { color: #c62828; }\n";
    echo "</style>\n</head>\n<body>\n";
    echo "<h1>Database Export Error</h1>\n";
    echo "<div class='error'>\n";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>\n";
    echo "</body>\n</html>";
    exit;
}
