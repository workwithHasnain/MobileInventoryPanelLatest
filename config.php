<?php

/**
 * Configuration loader for the Mobile Phone Management System
 */

// Load environment variables from .env file
function loadEnv($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
    return true;
}

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
} else {
    // Fallback to default values if .env doesn't exist
    $_ENV['DATABASE_URL'] = 'postgresql://postgres:password@localhost:5432/mobile_tech_hub';
    $_ENV['PGDATABASE'] = 'mobile_tech_hub';
    $_ENV['PGHOST'] = 'localhost';
    $_ENV['PGPORT'] = '5432';
    $_ENV['PGUSER'] = 'postgres';
    $_ENV['PGPASSWORD'] = 'password';
}

// Set default values for other config
$_ENV['APP_NAME'] = $_ENV['APP_NAME'] ?? 'Mobile Phone Management System';
$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'development';
$_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? 'true';

// Base URL configuration - Change based on environment
// Localhost: /MobileInventoryPanelLatest/
// Production: /
$base = '/MobileInventoryPanelLatest/'; // Adjust this if deploying to production or a different subdirectory

// Canonical base URL for SEO (fixed domain)
$canonicalBase = 'https://www.devicesarena.com';
