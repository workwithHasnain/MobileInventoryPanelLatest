<?php

/**
 * Create Reviews Table Script
 * Browser-ready script to create the reviews table for one-to-one association between posts and phones
 */

require_once 'config.php';
require_once 'database_functions.php';

// Database connection
try {
    $pdo = getConnection();

    // First, drop the old table if it exists
    $dropSql = "DROP TABLE IF EXISTS post_phone_reviews";
    $pdo->exec($dropSql);

    // SQL to create reviews table (PostgreSQL syntax)
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id SERIAL PRIMARY KEY,
        phone_id INTEGER NOT NULL UNIQUE REFERENCES phones(id) ON DELETE CASCADE,
        post_id INTEGER NOT NULL UNIQUE REFERENCES posts(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);

    // Create indexes
    $indexQueries = [
        "CREATE INDEX IF NOT EXISTS idx_reviews_phone_id ON reviews(phone_id)",
        "CREATE INDEX IF NOT EXISTS idx_reviews_post_id ON reviews(post_id)"
    ];

    foreach ($indexQueries as $query) {
        $pdo->exec($query);
    }

    $message = "✓ Old table removed (if existed)<br>✓ Reviews table created successfully!<br>✓ Indexes created successfully!";
    $status = "success";
} catch (Exception $e) {
    $status = "error";
    $message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Reviews Table</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .message {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .table-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .table-info ul {
            list-style-position: inside;
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }

        .table-info code {
            background-color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #764ba2;
        }

        .details {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .details h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .details p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 8px;
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
        <h1>📊 Reviews Table Setup</h1>
        <p class="subtitle">Mobile Inventory Panel - Database Configuration</p>

        <div class="message <?php echo isset($status) ? $status : 'info'; ?>">
            <?php echo isset($message) ? $message : 'Processing...'; ?>
        </div>

        <div class="table-info">
            <h3>📋 Reviews Table Structure</h3>
            <ul>
                <li><code>id</code> - Primary key (Auto-increment)</li>
                <li><code>phone_id</code> - Reference to phones table (UNIQUE - one-to-one)</li>
                <li><code>post_id</code> - Reference to posts table (UNIQUE - one-to-one)</li>
            </ul>
        </div>

        <div class="details">
            <h4>📌 Key Features:</h4>
            <p>✓ <strong>One-to-One Association:</strong> Each post can be linked to only one phone, and vice versa (UNIQUE constraints)</p>
            <p>✓ <strong>Optional Association:</strong> Posts and phones can exist without being reviewed/linked</p>
            <p>✓ <strong>Cascading Deletes:</strong> If a phone or post is deleted, the review association is automatically removed</p>
            <p>✓ <strong>Indexed:</strong> Both phone_id and post_id are indexed for fast queries</p>
        </div>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Reviews Table</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .message {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .table-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .table-info ul {
            list-style-position: inside;
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }

        .table-info code {
            background-color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #764ba2;
        }

        .details {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .details h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .details p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 8px;
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
        <h1>📊 Reviews Table Setup</h1>
        <p class="subtitle">Mobile Inventory Panel - Database Configuration</p>

        <div class="message <?php echo isset($status) ? $status : 'info'; ?>">
            <?php echo isset($message) ? $message : 'Processing...'; ?>
        </div>

        <div class="table-info">
            <h3>📋 Reviews Table Structure</h3>
            <ul>
                <li><code>id</code> - Primary key (Auto-increment)</li>
                <li><code>phone_id</code> - Reference to phones table (UNIQUE - one-to-one)</li>
                <li><code>post_id</code> - Reference to posts table (UNIQUE - one-to-one)</li>
            </ul>
        </div>

        <div class="details">
            <h4>📌 Key Features:</h4>
            <p>✓ <strong>One-to-One Association:</strong> Each post can be linked to only one phone, and vice versa (UNIQUE constraints)</p>
            <p>✓ <strong>Optional Association:</strong> Posts and phones can exist without being reviewed/linked</p>
            <p>✓ <strong>Cascading Deletes:</strong> If a phone or post is deleted, the review association is automatically removed</p>
            <p>✓ <strong>Indexed:</strong> Both phone_id and post_id are indexed for fast queries</p>
        </div>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>

</html>