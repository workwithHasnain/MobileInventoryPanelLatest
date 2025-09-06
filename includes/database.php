<?php
/**
 * Database include file - redirects to main database_functions.php
 * This file maintains compatibility for includes that expect includes/database.php
 */

// Include the main database functions file
require_once __DIR__ . '/../database_functions.php';

// Legacy compatibility - create aliases if needed
if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection() {
        return getConnection();
    }
}