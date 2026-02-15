<?php

/** Database connection and utility functions for PostgreSQL */

// Load configuration
require_once __DIR__ . '/config.php';

/**
 * Get PDO connection to PostgreSQL database
 *
 * @return PDO Database connection
 * @throws Exception If connection fails
 */
function getConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            // Get database URL from environment
            $database_url = getenv('DATABASE_URL');

            if (!$database_url) {
                // Fallback to default values if DATABASE_URL is not set
                $host = getenv('PGHOST') ?: 'localhost';
                $port = getenv('PGPORT') ?: '5432';
                $dbname = getenv('PGDATABASE') ?: 'mobile_tech_hub';
                $user = getenv('PGUSER') ?: 'postgres';
                $password = getenv('PGPASSWORD') ?: 'password';

                // Create PDO connection string
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

                // Create PDO instance
                $pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                return $pdo;
            }

            // Parse the database URL
            $db_parts = parse_url($database_url);

            $host = $db_parts['host'];
            $port = $db_parts['port'] ?? 5432;
            $dbname = ltrim($db_parts['path'], '/');
            $user = $db_parts['user'];
            $password = $db_parts['pass'];

            // Create PDO connection string
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            // Create PDO instance
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw $e;
        }
    }

    return $pdo;
}

/**
 * Execute a query and return statement object
 *
 * @param string $query SQL query
 * @param array $params Query parameters
 * @return PDOStatement Statement object
 */
function executeQuery($query, $params = [])
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log('Query execution error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Execute an INSERT, UPDATE, or DELETE query
 *
 * @param string $query SQL query
 * @param array $params Query parameters
 * @return bool Success status
 */
function executeUpdate($query, $params = [])
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log('Update execution error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get the last inserted ID
 *
 * @return string|false Last insert ID or false on failure
 */
function getLastInsertId()
{
    try {
        $pdo = getConnection();
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('Get last insert ID error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Begin a database transaction
 *
 * @return bool Success status
 */
function beginTransaction()
{
    try {
        $pdo = getConnection();
        return $pdo->beginTransaction();
    } catch (Exception $e) {
        error_log('Begin transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Commit a database transaction
 *
 * @return bool Success status
 */
function commitTransaction()
{
    try {
        $pdo = getConnection();
        return $pdo->commit();
    } catch (Exception $e) {
        error_log('Commit transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rollback a database transaction
 *
 * @return bool Success status
 */
function rollbackTransaction()
{
    try {
        $pdo = getConnection();
        return $pdo->rollback();
    } catch (Exception $e) {
        error_log('Rollback transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a table exists
 *
 * @param string $table_name Table name to check
 * @return bool True if table exists, false otherwise
 */
function tableExists($table_name)
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = ?
        ");
        $stmt->execute([$table_name]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log('Table exists check error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Escape string for safe SQL usage (though prepared statements are preferred)
 *
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeString($string)
{
    try {
        $pdo = getConnection();
        return $pdo->quote($string);
    } catch (Exception $e) {
        error_log('String escape error: ' . $e->getMessage());
        return "'" . str_replace("'", "''", $string) . "'";
    }
}

/**
 * Get popular device comparisons from database
 *
 * @param int $limit Number of comparisons to return (default: 10)
 * @return array Array of popular comparisons with device details
 */
function getPopularComparisons($limit = 10)
{
    try {
        $pdo = getConnection();

        $query = "
            SELECT 
                dc.device1_id,
                dc.device2_id,
                COUNT(*) as comparison_count,
                p1.name as device1_name,
                p1.slug as device1_slug,
                p1.image as device1_image,
                b1.name as device1_brand,
                p2.name as device2_name,
                p2.slug as device2_slug,
                p2.image as device2_image,
                b2.name as device2_brand
            FROM device_comparisons dc
            LEFT JOIN phones p1 ON dc.device1_id = CAST(p1.id AS VARCHAR)
            LEFT JOIN phones p2 ON dc.device2_id = CAST(p2.id AS VARCHAR)
            LEFT JOIN brands b1 ON p1.brand_id = b1.id
            LEFT JOIN brands b2 ON p2.brand_id = b2.id
            WHERE p1.id IS NOT NULL AND p2.id IS NOT NULL
            GROUP BY dc.device1_id, dc.device2_id, p1.name, p1.slug, p1.image, b1.name, p2.name, p2.slug, p2.image, b2.name
            ORDER BY comparison_count DESC
            LIMIT ?
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll();

        // Format results to match the expected structure
        $formattedResults = [];
        foreach ($results as $row) {
            $formattedResults[] = [
                'device1_id' => $row['device1_id'],
                'device2_id' => $row['device2_id'],
                'comparison_count' => (int)$row['comparison_count'],
                'device1_name' => $row['device1_name'],
                'device1_slug' => $row['device1_slug'] ?? '',
                'device2_name' => $row['device2_name'],
                'device2_slug' => $row['device2_slug'] ?? '',
                'device1_image' => $row['device1_image'] ?? '',
                'device2_image' => $row['device2_image'] ?? '',
                'device1_brand' => $row['device1_brand'] ?? '',
                'device2_brand' => $row['device2_brand'] ?? ''
            ];
        }

        return $formattedResults;
    } catch (Exception $e) {
        error_log('Get popular comparisons error: ' . $e->getMessage());
        return [];
    }
}
