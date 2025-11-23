<?php
/**
 * Fix PostgreSQL sequence for phones table
 * Run once to sync the sequence with existing data
 */

require_once 'database_functions.php';

try {
    $pdo = getConnection();
    
    // Get the maximum ID from phones table
    $stmt = $pdo->query("SELECT MAX(id) FROM phones");
    $maxId = $stmt->fetchColumn();
    
    if ($maxId === null) {
        $maxId = 0;
    }
    
    // Reset the sequence to maxId + 1
    $pdo->exec("SELECT setval('phones_id_seq', " . intval($maxId + 1) . ")");
    
    echo '<div style="background:#e8f5e9; padding:15px; border-radius:5px; border-left:4px solid #4CAF50;">';
    echo '<strong style="color:#2e7d32;">✓ Sequence Fixed!</strong><br>';
    echo 'Maximum ID in database: ' . $maxId . '<br>';
    echo 'Sequence reset to: ' . ($maxId + 1) . '<br>';
    echo 'New devices will now insert correctly starting from ID: ' . ($maxId + 1);
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="background:#ffebee; padding:15px; border-radius:5px; border-left:4px solid #f44336;">';
    echo '<strong style="color:#c62828;">✗ Error:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>
