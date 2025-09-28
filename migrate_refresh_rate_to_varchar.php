<?php
// Migration: Convert phones.refresh_rate to VARCHAR(50)
// Usage (CLI or web): php migrate_refresh_rate_to_varchar.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database_functions.php';

function getColumnType(PDO $pdo, string $table, string $column): ?string
{
    $stmt = $pdo->prepare("SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    $type = $stmt->fetchColumn();
    return $type !== false ? $type : null;
}

try {
    $pdo = getConnection();

    // Ensure table exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='phones'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        echo "Table 'phones' does not exist.\n";
        exit(0);
    }

    $currentType = getColumnType($pdo, 'phones', 'refresh_rate');
    if ($currentType === null) {
        echo "Column 'refresh_rate' does not exist on 'phones'.\n";
        exit(1);
    }

    echo "Current type of phones.refresh_rate: {$currentType}\n";

    // information_schema reports 'character varying' for VARCHAR
    $isAlreadyVarchar = (stripos($currentType, 'character varying') !== false || stripos($currentType, 'text') !== false);
    if ($isAlreadyVarchar) {
        echo "No change needed. Column is already a character type.\n";
        exit(0);
    }

    // Migrate within a transaction
    $pdo->beginTransaction();

    // Convert integer/numeric to text safely
    $sql = "ALTER TABLE phones ALTER COLUMN refresh_rate TYPE VARCHAR(50) USING refresh_rate::text";
    $pdo->exec($sql);

    $pdo->commit();
    echo "Successfully converted phones.refresh_rate to VARCHAR(50).\n";
    exit(0);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
