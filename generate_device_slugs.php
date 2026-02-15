<?php

/**
 * Device Slug Generator Script
 * Generates slugs for all devices and saves them to the database
 * Run this script once to populate the slug field for existing devices
 */

require_once 'database_functions.php';

// Function to convert device name to slug
function nameToSlug($name)
{
    // Convert to lowercase
    $slug = strtolower($name);

    // Replace spaces and special characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');

    return $slug;
}

// Main script
try {
    $pdo = getConnection();

    // Fetch ALL devices (regenerate all slugs)
    $stmt = $pdo->prepare("SELECT id, name FROM phones ORDER BY id ASC");
    $stmt->execute();
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Processing " . count($devices) . " devices...\n\n";

    $updated = 0;
    $failed = 0;

    foreach ($devices as $device) {
        $id = $device['id'];
        $name = $device['name'];

        // Generate slug from name
        $slug = nameToSlug($name);

        try {
            // Update device with slug
            $updateStmt = $pdo->prepare("UPDATE phones SET slug = ? WHERE id = ?");
            $updateStmt->execute([$slug, $id]);

            echo "✓ ID: $id | Name: $name | Slug: $slug\n";
            $updated++;
        } catch (Exception $e) {
            echo "✗ ID: $id | Name: $name | ERROR: " . $e->getMessage() . "\n";
            $failed++;
        }
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Summary:\n";
    echo "  Updated: $updated devices\n";
    echo "  Failed: $failed devices\n";
    echo "  Total: " . ($updated + $failed) . " devices\n";
    echo str_repeat("=", 60) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
