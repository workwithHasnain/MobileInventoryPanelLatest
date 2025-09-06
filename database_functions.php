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
