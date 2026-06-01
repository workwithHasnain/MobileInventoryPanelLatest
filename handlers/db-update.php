<?php
// db-update.php
// Minimal helper script for Database operations

require_once __DIR__ . '/../includes/database.php';

$message = '';
$error = '';

function getDbConfig() {
    $database_url = getenv('DATABASE_URL');
    if ($database_url) {
        $db_parts = parse_url($database_url);
        return [
            'host' => $db_parts['host'],
            'port' => $db_parts['port'] ?? 5432,
            'dbname' => ltrim($db_parts['path'], '/'),
            'user' => $db_parts['user'],
            'password' => $db_parts['pass']
        ];
    } else {
        return [
            'host' => getenv('PGHOST') ?: 'localhost',
            'port' => getenv('PGPORT') ?: '5432',
            'dbname' => getenv('PGDATABASE') ?: 'mobile_tech_hub',
            'user' => getenv('PGUSER') ?: 'postgres',
            'password' => getenv('PGPASSWORD') ?: 'password'
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'wipe') {
        try {
            $pdo = getConnection();
            // Drop tables
            $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tables)) {
                $dropList = array_map(function($t) { return '"'.$t.'"'; }, $tables);
                $pdo->exec("DROP TABLE " . implode(', ', $dropList) . " CASCADE");
            }
            
            // Drop sequences (if any stand-alone exist)
            $stmtSeq = $pdo->query("SELECT sequencename FROM pg_sequences WHERE schemaname = 'public'");
            $seqs = $stmtSeq->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($seqs)) {
                $dropSeqList = array_map(function($t) { return '"'.$t.'"'; }, $seqs);
                $pdo->exec("DROP SEQUENCE IF EXISTS " . implode(', ', $dropSeqList) . " CASCADE");
            }
            
            $message = "Database wiped successfully. Dropped " . count($tables) . " tables.";
        } catch (Exception $e) {
            $error = "Wipe failed: " . $e->getMessage();
        }
    } elseif ($action === 'feed') {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $sqlContent = file_get_contents($_FILES['sql_file']['tmp_name']);
            try {
                $pdo = getConnection();
                $pdo->exec($sqlContent);
                $message = "Database fed successfully with the uploaded SQL file.";
            } catch (Exception $e) {
                $error = "Feed failed: " . $e->getMessage();
            }
        } else {
            $error = "Please upload a valid SQL file.";
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $cfg = getDbConfig();
    putenv("PGPASSWORD=" . $cfg['password']);
    
    $filename = "db_dump_" . date('Y-m-d_H-i-s') . ".sql";
    $tempFile = sys_get_temp_dir() . '/' . $filename;
    
    $pg_dump_cmd = "pg_dump";
    
    // Check common Windows PostgreSQL installation paths if on Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $paths = [
            "C:\\Program Files\\PostgreSQL\\16\\bin\\pg_dump.exe",
            "C:\\Program Files\\PostgreSQL\\15\\bin\\pg_dump.exe",
            "C:\\Program Files\\PostgreSQL\\14\\bin\\pg_dump.exe",
            "C:\\Program Files\\PostgreSQL\\13\\bin\\pg_dump.exe"
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) {
                $pg_dump_cmd = "\"$p\"";
                break;
            }
        }
    }
    
    $dumpCommand = "$pg_dump_cmd -U {$cfg['user']} -h {$cfg['host']} -p {$cfg['port']} -d {$cfg['dbname']} -F p --clean --if-exists -f \"$tempFile\" 2>&1";
    
    $output = [];
    $result = 0;
    exec($dumpCommand, $output, $result);
    
    if ($result === 0 && file_exists($tempFile)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);
        unlink($tempFile);
        exit;
    } else {
        // Fallback: Dump only data using PHP if pg_dump fails
        $fallbackDump = "-- Database Dump (PHP Fallback)\n";
        $fallbackDump .= "-- Note: pg_dump was not found or failed, so this dump ONLY contains data, not the schema/tables.\n";
        $fallbackDump .= "-- Please run the complete_database_schema.sql first if feeding an empty database.\n\n";
        
        try {
            $pdo = getConnection();
            $tablesStmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $dataStmt = $pdo->query("SELECT * FROM \"$table\"");
                $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $fallbackDump .= "-- Data for table: $table\n";
                    foreach ($rows as $row) {
                        $cols = array_keys($row);
                        $vals = array_map(function($v) use ($pdo) {
                            return $v === null ? 'NULL' : $pdo->quote($v);
                        }, array_values($row));
                        $fallbackDump .= "INSERT INTO \"$table\" (\"" . implode('", "', $cols) . "\") VALUES (" . implode(", ", $vals) . ");\n";
                    }
                    $fallbackDump .= "\n";
                }
            }
            
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="data_fallback_' . $filename . '"');
            echo $fallbackDump;
            exit;
        } catch (Exception $e) {
             $error = "Download failed. pg_dump error: " . implode("\n", $output) . "\nPHP Fallback error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Helper</title>
</head>
<body>
    <h1>Database Helper</h1>
    
    <?php if ($message): ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p style="color: red; font-weight: bold;"><?php echo nl2br(htmlspecialchars($error)); ?></p>
    <?php endif; ?>

    <hr>
    
    <h3>1. Download DB</h3>
    <p>Downloads the current state of the database dynamically (schema + data if pg_dump is available, otherwise data-only fallback).</p>
    <form action="db-update.php" method="GET">
        <input type="hidden" name="action" value="download">
        <button type="submit">Download DB</button>
    </form>
    
    <hr>

    <h3>2. Feed DB</h3>
    <p>Injects a selected SQL file into the database.</p>
    <form action="db-update.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="feed">
        <input type="file" name="sql_file" accept=".sql" required>
        <button type="submit">Upload & Feed DB</button>
    </form>
    
    <hr>

    <h3>3. Wipe DB (Sudo Option)</h3>
    <p><strong>Warning:</strong> This will drop all tables and sequences in the public schema.</p>
    <form action="db-update.php" method="POST" onsubmit="return confirm('Are you sure you want to WIPE the entire database? This cannot be undone.');">
        <input type="hidden" name="action" value="wipe">
        <button type="submit" style="color: red;">Wipe DB</button>
    </form>
</body>
</html>
