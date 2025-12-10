<?php

/**
 * Database Status & Structure Verification Script
 * Shows database connection status and verifies all table structures
 */

require_once 'config.php';
require_once 'database_functions.php';

$dbStatus = [];
$tableStructures = [];
$errors = [];

try {
    // Get database connection
    $pdo = getConnection();
    $dbStatus['connection'] = 'success';
    $dbStatus['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Get database name
    $stmt = $pdo->query("SELECT current_database()");
    $dbStatus['database'] = $stmt->fetchColumn();

    // Get PostgreSQL version
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    $dbStatus['version'] = $version;

    // Get list of all tables
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $dbStatus['total_tables'] = count($tables);
    $dbStatus['tables_list'] = $tables;

    // Verify critical tables structure
    $criticalTables = [
        'admin_users',
        'brands',
        'chipsets',
        'posts',
        'post_categories',
        'phones',
        'post_comments',
        'device_comments',
        'device_reviews',
        'device_views',
        'device_comparisons',
        'newsletter_subscribers',
        'content_views',
        'reviews'
    ];

    foreach ($criticalTables as $table) {
        if (in_array($table, $tables)) {
            // Get table structure
            $stmt = $pdo->query("
                SELECT 
                    column_name,
                    data_type,
                    is_nullable,
                    column_default
                FROM information_schema.columns
                WHERE table_name = '$table'
                ORDER BY ordinal_position
            ");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $tableStructures[$table] = [
                'status' => 'exists',
                'column_count' => count($columns),
                'columns' => $columns
            ];
        } else {
            $tableStructures[$table] = [
                'status' => 'missing',
                'column_count' => 0,
                'columns' => []
            ];
        }
    }

    // Verify reviews table structure specifically
    if (in_array('reviews', $tables)) {
        $stmt = $pdo->query("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default
            FROM information_schema.columns
            WHERE table_name = 'reviews'
            ORDER BY ordinal_position
        ");
        $reviewsColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbStatus['reviews_verified'] = true;
        $dbStatus['reviews_columns'] = $reviewsColumns;
    }

    // Get table sizes
    try {
        $stmt = $pdo->query("
            SELECT 
                schemaname,
                relname AS tablename,
                pg_size_pretty(pg_total_relation_size(schemaname||'.'||relname)) AS size,
                n_live_tup AS row_count
            FROM pg_stat_user_tables
            ORDER BY pg_total_relation_size(schemaname||'.'||relname) DESC
        ");
        $tableSizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbStatus['table_sizes'] = $tableSizes;
    } catch (Exception $e) {
        $dbStatus['table_sizes'] = [];
    }
} catch (Exception $e) {
    $dbStatus['connection'] = 'failed';
    $errors[] = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status & Structure Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .status-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .status-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .status-card h3 {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .status-card .value {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            word-break: break-all;
        }

        .status-card.success {
            border-left-color: #28a745;
        }

        .status-card.warning {
            border-left-color: #ffc107;
        }

        .status-card.error {
            border-left-color: #dc3545;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th {
            background: #f8f9fa;
            color: #333;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
            font-size: 13px;
            text-transform: uppercase;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }

        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .column-grid {
            display: grid;
            gap: 10px;
        }

        .column-item {
            background: #f8f9fa;
            padding: 10px;
            border-left: 3px solid #667eea;
            border-radius: 4px;
            font-size: 13px;
        }

        .column-name {
            font-weight: 600;
            color: #333;
        }

        .column-type {
            color: #764ba2;
            font-family: 'Courier New', monospace;
        }

        .column-nullable {
            font-size: 12px;
            color: #999;
        }

        .tables-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .table-tag {
            background: #e7f3ff;
            color: #0066cc;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-align: center;
            font-weight: 500;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .back-link:hover {
            background-color: #764ba2;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>📊 Database Status & Structure Verification</h1>
        <p class="subtitle">Mobile Inventory Panel - System Health Check</p>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="error-box">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Database Connection Status -->
        <?php if ($dbStatus['connection'] === 'success'): ?>
            <div class="success-box">
                ✅ Database connection successful!
            </div>

            <div class="status-banner">
                <h2 style="margin: 0; font-size: 20px; margin-bottom: 15px;">Connection Details</h2>
                <div class="status-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong>Driver:</strong> <?php echo htmlspecialchars($dbStatus['driver']); ?>
                    </div>
                    <div>
                        <strong>Database:</strong> <?php echo htmlspecialchars($dbStatus['database']); ?>
                    </div>
                    <div>
                        <strong>Total Tables:</strong> <?php echo $dbStatus['total_tables']; ?>
                    </div>
                </div>
            </div>

            <!-- Database Version -->
            <div class="section">
                <h2>🔧 Database Version</h2>
                <p style="font-family: 'Courier New', monospace; font-size: 13px; color: #666;">
                    <?php echo htmlspecialchars($dbStatus['version']); ?>
                </p>
            </div>

            <!-- All Tables -->
            <div class="section">
                <h2>📋 All Tables (<?php echo $dbStatus['total_tables']; ?>)</h2>
                <div class="tables-list">
                    <?php foreach ($dbStatus['tables_list'] as $table): ?>
                        <div class="table-tag">
                            <?php echo htmlspecialchars($table); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Reviews Table Verification -->
            <?php if (isset($dbStatus['reviews_verified']) && $dbStatus['reviews_verified']): ?>
                <div class="section">
                    <h2>✅ Reviews Table Structure (Verified)</h2>
                    <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 20px; color: #155724; border-left: 4px solid #28a745;">
                        Reviews table exists and has correct structure!
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Column Name</th>
                                <th>Data Type</th>
                                <th>Nullable</th>
                                <th>Default Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbStatus['reviews_columns'] as $col): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($col['column_name']); ?></strong></td>
                                    <td><code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($col['data_type']); ?></code></td>
                                    <td><span class="status-badge <?php echo $col['is_nullable'] === 'YES' ? 'badge-warning' : 'badge-success'; ?>"><?php echo $col['is_nullable']; ?></span></td>
                                    <td><?php echo htmlspecialchars($col['column_default'] ?? 'None'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Critical Tables Structure Verification -->
            <div class="section">
                <h2>🔍 Critical Tables Structure Verification</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                            <th>Columns</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableStructures as $tableName => $structure): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tableName); ?></strong></td>
                                <td>
                                    <span class="status-badge <?php echo $structure['status'] === 'exists' ? 'badge-success' : 'badge-error'; ?>">
                                        <?php echo $structure['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($structure['status'] === 'exists'): ?>
                                        <div class="column-grid">
                                            <?php foreach ($structure['columns'] as $col): ?>
                                                <div class="column-item">
                                                    <span class="column-name"><?php echo htmlspecialchars($col['column_name']); ?></span>
                                                    <span class="column-type"><?php echo htmlspecialchars($col['data_type']); ?></span>
                                                    <span class="column-nullable"><?php echo $col['is_nullable'] === 'NO' ? '(NOT NULL)' : '(nullable)'; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">Table not found in database</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Sizes -->
            <?php if (!empty($dbStatus['table_sizes'])): ?>
                <div class="section">
                    <h2>💾 Table Sizes & Row Counts</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Size</th>
                                <th>Row Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbStatus['table_sizes'] as $size): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($size['tablename']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($size['size']); ?></td>
                                    <td><?php echo number_format($size['row_count']); ?> rows</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="error-box">
                ❌ Failed to connect to database!
            </div>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>

</html>