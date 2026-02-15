<?php

/**
 * Migration Script: Add SEO Fields to Phones Table
 * 
 * This script adds three new columns to the phones table:
 * - slug (VARCHAR 255, UNIQUE) - URL-friendly identifier
 * - meta_title (VARCHAR 255) - SEO meta title
 * - meta_desc (TEXT) - SEO meta description
 * 
 * Run this script once from your browser to update the database.
 * URL: http://localhost/MobileInventoryPanelLatest/migrate_add_seo_fields.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'database_functions.php';

// Check if script has already been run
$migration_marker = __DIR__ . '/.migration_seo_fields_done';
if (file_exists($migration_marker)) {
    die('<h2 style="color: orange;">⚠️ Migration Already Completed</h2><p>This migration has already been run. Delete the file <code>.migration_seo_fields_done</code> if you need to run it again.</p>');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Add SEO Fields</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }

        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .step {
            margin: 10px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }

        code {
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔧 Database Migration: Add SEO Fields</h1>

        <div class="info">
            <strong>Migration Details:</strong><br>
            This script will add the following columns to the <code>phones</code> table:
            <ul>
                <li><strong>slug</strong> (VARCHAR 255, UNIQUE) - URL-friendly device identifier</li>
                <li><strong>meta_title</strong> (VARCHAR 255) - Custom SEO title for search engines</li>
                <li><strong>meta_desc</strong> (TEXT) - Custom SEO description for search engines</li>
            </ul>
        </div>

        <?php
        try {
            $pdo = getConnection();
            $messages = [];
            $errors = [];

            // Start transaction
            $pdo->beginTransaction();

            // Step 1: Add slug column
            $messages[] = "Step 1: Adding 'slug' column...";
            try {
                $pdo->exec("ALTER TABLE phones ADD COLUMN IF NOT EXISTS slug VARCHAR(255) UNIQUE");
                $messages[] = "✓ 'slug' column added successfully";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $messages[] = "ℹ️ 'slug' column already exists";
                } else {
                    throw $e;
                }
            }

            // Step 2: Add meta_title column
            $messages[] = "Step 2: Adding 'meta_title' column...";
            try {
                $pdo->exec("ALTER TABLE phones ADD COLUMN IF NOT EXISTS meta_title VARCHAR(255)");
                $messages[] = "✓ 'meta_title' column added successfully";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $messages[] = "ℹ️ 'meta_title' column already exists";
                } else {
                    throw $e;
                }
            }

            // Step 3: Add meta_desc column
            $messages[] = "Step 3: Adding 'meta_desc' column...";
            try {
                $pdo->exec("ALTER TABLE phones ADD COLUMN IF NOT EXISTS meta_desc TEXT");
                $messages[] = "✓ 'meta_desc' column added successfully";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $messages[] = "ℹ️ 'meta_desc' column already exists";
                } else {
                    throw $e;
                }
            }

            // Step 4: Generate slugs for existing devices that don't have them
            $messages[] = "Step 4: Generating slugs for existing devices...";
            $stmt = $pdo->query("SELECT id, name, brand FROM phones WHERE slug IS NULL OR slug = ''");
            $devicesWithoutSlugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($devicesWithoutSlugs) > 0) {
                $updateStmt = $pdo->prepare("UPDATE phones SET slug = :slug WHERE id = :id");
                $slugCounts = [];

                foreach ($devicesWithoutSlugs as $device) {
                    // Generate slug from brand and name
                    $baseSlug = strtolower(trim($device['brand'] ?? '')) . '-' . strtolower(trim($device['name'] ?? ''));
                    $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
                    $baseSlug = trim($baseSlug, '-');

                    // Ensure uniqueness
                    $slug = $baseSlug;
                    $counter = 1;

                    // Track slugs to avoid duplicates within this migration
                    if (isset($slugCounts[$slug])) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                        while (isset($slugCounts[$slug])) {
                            $slug = $baseSlug . '-' . $counter;
                            $counter++;
                        }
                    }

                    // Check database for existing slug
                    while (true) {
                        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE slug = :slug AND id != :id");
                        $checkStmt->execute([':slug' => $slug, ':id' => $device['id']]);
                        if ($checkStmt->fetchColumn() == 0) {
                            break;
                        }
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    $slugCounts[$slug] = true;
                    $updateStmt->execute([':slug' => $slug, ':id' => $device['id']]);
                }

                $messages[] = "✓ Generated slugs for " . count($devicesWithoutSlugs) . " device(s)";
            } else {
                $messages[] = "ℹ️ All devices already have slugs";
            }

            // Commit transaction
            $pdo->commit();

            // Create marker file to prevent re-running
            file_put_contents($migration_marker, date('Y-m-d H:i:s'));

            echo '<div class="success">';
            echo '<h2>✅ Migration Completed Successfully!</h2>';
            foreach ($messages as $message) {
                echo '<div class="step">' . htmlspecialchars($message) . '</div>';
            }
            echo '</div>';

            // Show table structure
            echo '<div class="info">';
            echo '<h3>Updated Table Structure:</h3>';
            $columnsStmt = $pdo->query("
        SELECT column_name, data_type, character_maximum_length, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'phones' 
        AND column_name IN ('slug', 'meta_title', 'meta_desc')
        ORDER BY ordinal_position
    ");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($columns) {
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="background-color: #e9ecef;"><th style="padding: 8px; border: 1px solid #dee2e6;">Column</th><th style="padding: 8px; border: 1px solid #dee2e6;">Type</th><th style="padding: 8px; border: 1px solid #dee2e6;">Nullable</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td style="padding: 8px; border: 1px solid #dee2e6;"><code>' . htmlspecialchars($col['column_name']) . '</code></td>';
                    $type = $col['data_type'];
                    if ($col['character_maximum_length']) {
                        $type .= '(' . $col['character_maximum_length'] . ')';
                    }
                    echo '<td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($type) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($col['is_nullable']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            echo '</div>';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            echo '<div class="error">';
            echo '<h2>❌ Migration Failed</h2>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . htmlspecialchars($e->getLine()) . '</p>';
            echo '</div>';
        }
        ?>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>

</html>