<?php

/**
 * Database Column Migration Script
 * Renames section columns to match new terminology
 * 
 * Changes:
 * - platform → hardware
 * - sound → multimedia
 * - comms → connectivity
 * - misc → general_info
 * 
 * Run this once through browser, then delete the file
 */

// Require database connection
require_once 'includes/database.php';

$success = true;
$messages = [];

// Get PDO connection
try {
    $pdo = getConnection();
} catch (Exception $e) {
    die('<div style="background:#fee; padding:10px; border:1px solid red;">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Get current column names
try {
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'phones'
        ORDER BY column_name
    ");
    $current_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check which columns exist
    $platform_exists = in_array('platform', $current_columns);
    $sound_exists = in_array('sound', $current_columns);
    $comms_exists = in_array('comms', $current_columns);
    $misc_exists = in_array('misc', $current_columns);

    // Check if new columns already exist
    $hardware_exists = in_array('hardware', $current_columns);
    $multimedia_exists = in_array('multimedia', $current_columns);
    $connectivity_exists = in_array('connectivity', $current_columns);
    $general_info_exists = in_array('general_info', $current_columns);
} catch (Exception $e) {
    die('<div style="background:#fee; padding:10px; border:1px solid red;">Error checking columns: ' . htmlspecialchars($e->getMessage()) . '</div>');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Column Rename</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }

        .status-box {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .status-info {
            background: #e3f2fd;
            border-color: #2196F3;
            color: #1565c0;
        }

        .status-success {
            background: #e8f5e9;
            border-color: #4CAF50;
            color: #2e7d32;
        }

        .status-warning {
            background: #fff3e0;
            border-color: #FF9800;
            color: #e65100;
        }

        .status-error {
            background: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f5f5f5;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        .step {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #007bff;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔄 Database Column Migration</h1>

        <div class="status-box status-info">
            <strong>Migration Purpose:</strong><br>
            This script renames database columns to match the new section terminology.
        </div>

        <h2>Current Column Status</h2>
        <table>
            <thead>
                <tr>
                    <th>Old Name</th>
                    <th>New Name</th>
                    <th>Old Exists</th>
                    <th>New Exists</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>platform</code></td>
                    <td><code>hardware</code></td>
                    <td><?php echo $platform_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td><?php echo $hardware_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td>
                        <?php if ($platform_exists && !$hardware_exists): ?>
                            <span style="color: #ff9800;">Ready to migrate</span>
                        <?php elseif ($hardware_exists && !$platform_exists): ?>
                            <span style="color: #4CAF50;">✓ Already migrated</span>
                        <?php elseif ($hardware_exists && $platform_exists): ?>
                            <span style="color: #f44336;">⚠ Both exist (conflict)</span>
                        <?php else: ?>
                            <span style="color: #999;">Neither exists</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>sound</code></td>
                    <td><code>multimedia</code></td>
                    <td><?php echo $sound_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td><?php echo $multimedia_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td>
                        <?php if ($sound_exists && !$multimedia_exists): ?>
                            <span style="color: #ff9800;">Ready to migrate</span>
                        <?php elseif ($multimedia_exists && !$sound_exists): ?>
                            <span style="color: #4CAF50;">✓ Already migrated</span>
                        <?php elseif ($multimedia_exists && $sound_exists): ?>
                            <span style="color: #f44336;">⚠ Both exist (conflict)</span>
                        <?php else: ?>
                            <span style="color: #999;">Neither exists</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>comms</code></td>
                    <td><code>connectivity</code></td>
                    <td><?php echo $comms_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td><?php echo $connectivity_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td>
                        <?php if ($comms_exists && !$connectivity_exists): ?>
                            <span style="color: #ff9800;">Ready to migrate</span>
                        <?php elseif ($connectivity_exists && !$comms_exists): ?>
                            <span style="color: #4CAF50;">✓ Already migrated</span>
                        <?php elseif ($connectivity_exists && $comms_exists): ?>
                            <span style="color: #f44336;">⚠ Both exist (conflict)</span>
                        <?php else: ?>
                            <span style="color: #999;">Neither exists</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><code>misc</code></td>
                    <td><code>general_info</code></td>
                    <td><?php echo $misc_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td><?php echo $general_info_exists ? '✅ Yes' : '❌ No'; ?></td>
                    <td>
                        <?php if ($misc_exists && !$general_info_exists): ?>
                            <span style="color: #ff9800;">Ready to migrate</span>
                        <?php elseif ($general_info_exists && !$misc_exists): ?>
                            <span style="color: #4CAF50;">✓ Already migrated</span>
                        <?php elseif ($general_info_exists && $misc_exists): ?>
                            <span style="color: #f44336;">⚠ Both exist (conflict)</span>
                        <?php else: ?>
                            <span style="color: #999;">Neither exists</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php
        // Check if all migrations are complete
        $all_migrated = !$platform_exists && !$sound_exists && !$comms_exists && !$misc_exists &&
            $hardware_exists && $multimedia_exists && $connectivity_exists && $general_info_exists;

        $can_migrate = ($platform_exists || $sound_exists || $comms_exists || $misc_exists) &&
            !($hardware_exists || $multimedia_exists || $connectivity_exists || $general_info_exists);
        ?>

        <?php if ($all_migrated): ?>
            <div class="status-box status-success">
                <strong>✓ Migration Complete</strong><br>
                All columns have been successfully renamed. You can now delete this migration script.
            </div>
        <?php elseif ($can_migrate): ?>
            <div class="status-box status-warning">
                <strong>⚠ Ready to Migrate</strong><br>
                Old columns exist and new columns don't. Click "Run Migration" to proceed.
            </div>
        <?php else: ?>
            <div class="status-box status-error">
                <strong>✗ Migration Status Issue</strong><br>
                Please check the table above. There might be a conflict or incomplete previous migration.
            </div>
        <?php endif; ?>

        <?php if ($can_migrate): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="migrate">
                <button type="submit" class="btn btn-success" onclick="return confirm('This will rename database columns. Make sure you have a backup. Continue?');">
                    ▶ Run Migration Now
                </button>
            </form>
        <?php endif; ?>

        <?php
        // Handle migration execution
        if ($_POST && isset($_POST['action']) && $_POST['action'] === 'migrate'):
            $migrations = [
                ['old' => 'platform', 'new' => 'hardware', 'exists' => $platform_exists],
                ['old' => 'sound', 'new' => 'multimedia', 'exists' => $sound_exists],
                ['old' => 'comms', 'new' => 'connectivity', 'exists' => $comms_exists],
                ['old' => 'misc', 'new' => 'general_info', 'exists' => $misc_exists],
            ];

            $migration_results = [];

            foreach ($migrations as $migration):
                if (!$migration['exists']) {
                    $migration_results[] = [
                        'old' => $migration['old'],
                        'new' => $migration['new'],
                        'status' => 'skipped',
                        'message' => 'Column does not exist'
                    ];
                    continue;
                }

                try {
                    $sql = "ALTER TABLE phones RENAME COLUMN " . $migration['old'] . " TO " . $migration['new'];
                    $pdo->exec($sql);

                    $migration_results[] = [
                        'old' => $migration['old'],
                        'new' => $migration['new'],
                        'status' => 'success',
                        'message' => 'Successfully renamed'
                    ];
                } catch (Exception $e) {
                    $migration_results[] = [
                        'old' => $migration['old'],
                        'new' => $migration['new'],
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            endforeach;
        ?>

            <h2>Migration Results</h2>
            <table>
                <thead>
                    <tr>
                        <th>Old Name</th>
                        <th>New Name</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migration_results as $result): ?>
                        <tr>
                            <td><code><?php echo $result['old']; ?></code></td>
                            <td><code><?php echo $result['new']; ?></code></td>
                            <td>
                                <?php if ($result['status'] === 'success'): ?>
                                    <span style="color: #4CAF50; font-weight: bold;">✓ Success</span>
                                <?php elseif ($result['status'] === 'error'): ?>
                                    <span style="color: #f44336; font-weight: bold;">✗ Failed</span>
                                <?php else: ?>
                                    <span style="color: #999;">⊘ Skipped</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="status-box status-success">
                <strong>✓ Migration Completed</strong><br>
                All operations have finished. Please verify the database columns were renamed correctly.
            </div>

            <a href="?refresh=1" class="btn btn-primary">↻ Refresh Status</a>

        <?php endif; ?>

        <h2>Next Steps</h2>
        <div class="step">
            <strong>1. After Migration:</strong><br>
            Update PHP code to use new column names:
            <ul>
                <li><code>device.php</code> - Update section mapping</li>
                <li><code>add_device.php</code> - Update form handling</li>
                <li><code>edit_device.php</code> - Update form handling</li>
                <li><code>compare.php</code> - Update display logic</li>
            </ul>
        </div>

        <div class="step">
            <strong>2. Update SQL Schema File:</strong><br>
            Edit <code>complete_database_schema.sql</code> to reflect new column names.
        </div>

        <div class="step">
            <strong>3. Delete This Script:</strong><br>
            Once migration is complete and verified, delete this file: <code>migrate_column_names.php</code>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
            <strong>Database Info:</strong><br>
            Host: <?php echo htmlspecialchars($_ENV['DB_HOST'] ?? 'localhost'); ?><br>
            Database: <?php echo htmlspecialchars($_ENV['DB_NAME'] ?? 'mobile_tech_hub'); ?><br>
            User: <?php echo htmlspecialchars($_ENV['DB_USER'] ?? 'postgres'); ?>
        </div>
    </div>
</body>

</html>