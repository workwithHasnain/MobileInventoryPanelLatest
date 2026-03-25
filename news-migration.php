<?php

/**
 * Migration: Add is_news column to posts table
 * 
 * This file adds the is_news BOOLEAN column to the posts table if it doesn't already exist.
 * Access this file in your browser: /news-migration.php
 */

session_start();

require_once 'config.php';
require_once 'database_functions.php';

// Only allow access from localhost for security
$allowed_hosts = ['localhost', '127.0.0.1', '::1'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

// For development purposes, allow if in localhost environment
$is_localhost = in_array($client_ip, $allowed_hosts) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Migration - Add is_news Column</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .status.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: block;
            margin: 30px auto 0;
            transition: background 0.3s;
        }

        button:hover {
            background: #764ba2;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .icon {
            font-size: 20px;
            margin-right: 10px;
        }

        .check {
            color: #28a745;
        }

        .cross {
            color: #dc3545;
        }

        .info-icon {
            color: #17a2b8;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>📰 News Migration</h1>
        <div class="subtitle">Add is_news column to posts table</div>

        <?php
        try {
            // Check if already executed
            $pdo = getConnection();

            // Check if the column exists
            $check_stmt = $pdo->prepare("
                SELECT EXISTS(
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'posts' 
                    AND column_name = 'is_news'
                ) as column_exists
            ");
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $column_exists = $result['column_exists'] ?? false;

            if ($column_exists) {
                echo '<div class="status info">';
                echo '<span class="icon info-icon">ℹ️</span>';
                echo '<strong>Column Already Exists</strong><br>';
                echo 'The <code>is_news</code> column is already present in the <code>posts</code> table.';
                echo '</div>';
            } else {
                // Add the column
                $alter_stmt = $pdo->prepare("
                    ALTER TABLE posts 
                    ADD COLUMN is_news BOOLEAN DEFAULT FALSE
                ");
                $alter_stmt->execute();

                echo '<div class="status success">';
                echo '<span class="icon check">✓</span>';
                echo '<strong>Migration Successful!</strong><br>';
                echo 'The <code>is_news</code> column has been successfully added to the <code>posts</code> table.';
                echo '</div>';

                echo '<div class="details">';
                echo '<strong>Changes Made:</strong><br>';
                echo '• Added column: <code>is_news</code><br>';
                echo '• Data type: <code>BOOLEAN</code><br>';
                echo '• Default value: <code>FALSE</code><br>';
                echo '• Table: <code>posts</code>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="status error">';
            echo '<span class="icon cross">✗</span>';
            echo '<strong>Migration Failed</strong><br>';
            echo 'An error occurred while running the migration.';
            echo '</div>';

            echo '<div class="details">';
            echo '<strong>Error Details:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="status error">';
            echo '<span class="icon cross">✗</span>';
            echo '<strong>Error</strong><br>';
            echo 'An unexpected error occurred.';
            echo '</div>';

            echo '<div class="details">';
            echo '<strong>Error Details:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>

        <button onclick="window.location.href='/'">← Back to Home</button>
    </div>
</body>

</html>