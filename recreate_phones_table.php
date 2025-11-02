<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'database_functions.php';

try {
    $pdo = getConnection();

    echo "<h2>Recreating phones table with final schema...</h2>";
    echo "<pre>";

    // Start transaction
    $pdo->beginTransaction();

    // Drop the phones table (CASCADE will handle foreign key constraints)
    echo "⏳ Dropping phones table...\n";
    $pdo->exec("DROP TABLE IF EXISTS phones CASCADE");
    echo "✅ Phones table dropped\n\n";

    // Create phones table with the final schema
    echo "⏳ Creating phones table with final schema...\n";
    $sql = "
    CREATE TABLE phones (
        id SERIAL PRIMARY KEY,
        -- Launch Information
        release_date DATE,
        name VARCHAR(255) NOT NULL,
        brand_id INTEGER REFERENCES brands(id) ON DELETE CASCADE,
        brand VARCHAR(100), -- For direct brand name storage
        year INTEGER,
        availability VARCHAR(50),
        price DECIMAL(10,2),
        image VARCHAR(255),
        images TEXT[], -- Array of image paths
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        -- Grouped specification columns (JSON stored as TEXT)
        network TEXT,
        launch TEXT,
        body TEXT,
        display TEXT,
        platform TEXT,
        memory TEXT,
        main_camera TEXT,
        selfie_camera TEXT,
        sound TEXT,
        comms TEXT,
        features TEXT,
        battery TEXT,
        misc TEXT,
        
        -- Highlight fields (for quick info display)
        weight VARCHAR(50),
        thickness VARCHAR(50),
        os VARCHAR(50),
        storage VARCHAR(50),
        card_slot BOOLEAN,
        
        -- Stats fields (for stats cards display)
        display_size VARCHAR(50),
        display_resolution VARCHAR(100),
        main_camera_resolution VARCHAR(100),
        main_camera_video VARCHAR(100),
        ram VARCHAR(50),
        chipset_name VARCHAR(100),
        battery_capacity VARCHAR(50),
        wired_charging VARCHAR(100),
        wireless_charging VARCHAR(100)
    )";

    $pdo->exec($sql);
    echo "✅ Phones table created successfully\n\n";

    // Create indexes for better query performance
    echo "⏳ Creating indexes...\n";
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_phones_brand_id ON phones(brand_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_phones_name ON phones(name)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_phones_year ON phones(year)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_phones_price ON phones(price)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_phones_os ON phones(os)");
    echo "✅ Indexes created (brand_id, name, year, price, os)\n\n";

    // Commit transaction
    $pdo->commit();

    // Show final schema
    echo "📋 Final phones table schema:\n";
    echo "================================\n";
    $stmt = $pdo->query("
        SELECT column_name, data_type, character_maximum_length, is_nullable
        FROM information_schema.columns 
        WHERE table_name = 'phones' 
        ORDER BY ordinal_position
    ");

    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $type = $col['data_type'];
        if ($col['character_maximum_length']) {
            $type .= "({$col['character_maximum_length']})";
        }
        $nullable = $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
        echo sprintf("%-30s %-30s %s\n", $col['column_name'], $type, $nullable);
    }

    echo "\n✅ SUCCESS! Phones table has been recreated with the final schema.\n";
    echo "Total columns: " . count($columns) . "\n";
    echo "</pre>";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<pre style='color: red;'>";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<pre style='color: red;'>";
    echo "❌ UNEXPECTED ERROR: " . $e->getMessage() . "\n";
    echo "</pre>";
}
