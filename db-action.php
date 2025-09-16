<?php

/**
 * Database Action Script - Add Sample Data
 * This script adds 36 brands and 20 chipsets to the database
 */

// Include configuration
require_once 'config.php';

// Database connection using PostgreSQL
function getDatabaseConnection()
{
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $_ENV['PGHOST'],
        $_ENV['PGPORT'],
        $_ENV['PGDATABASE']
    );

    try {
        $pdo = new PDO($dsn, $_ENV['PGUSER'], $_ENV['PGPASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Add 36 brand instances to the database
 */
function addBrands()
{
    $pdo = getDatabaseConnection();

    $brands = [
        ['Samsung', 'South Korean multinational electronics company', 'https://samsung.com'],
        ['Apple', 'American multinational technology company', 'https://apple.com'],
        ['Xiaomi', 'Chinese electronics company', 'https://xiaomi.com'],
        ['OnePlus', 'Chinese smartphone manufacturer', 'https://oneplus.com'],
        ['Google', 'American multinational technology company', 'https://google.com'],
        ['Nothing', 'London-based consumer technology company', 'https://nothing.tech'],
        ['Huawei', 'Chinese multinational technology corporation', 'https://huawei.com'],
        ['Oppo', 'Chinese consumer electronics manufacturer', 'https://oppo.com'],
        ['Vivo', 'Chinese smartphone manufacturer', 'https://vivo.com'],
        ['Realme', 'Chinese smartphone brand', 'https://realme.com'],
        ['Honor', 'Chinese smartphone brand', 'https://honor.com'],
        ['Motorola', 'American telecommunications company', 'https://motorola.com'],
        ['Nokia', 'Finnish telecommunications company', 'https://nokia.com'],
        ['Sony', 'Japanese multinational conglomerate', 'https://sony.com'],
        ['Asus', 'Taiwanese multinational computer hardware company', 'https://asus.com'],
        ['LG', 'South Korean multinational electronics company', 'https://lg.com'],
        ['TCL', 'Chinese electronics company', 'https://tcl.com'],
        ['Lenovo', 'Chinese multinational technology company', 'https://lenovo.com'],
        ['ZTE', 'Chinese multinational telecommunications equipment company', 'https://zte.com'],
        ['Microsoft', 'American multinational technology corporation', 'https://microsoft.com'],
        ['BlackBerry', 'Canadian software company', 'https://blackberry.com'],
        ['Infinix', 'Hong Kong-based smartphone brand', 'https://infinix.com'],
        ['Tecno', 'Chinese smartphone manufacturer', 'https://tecno.com'],
        ['Itel', 'Chinese smartphone brand', 'https://itel.com'],
        ['Fairphone', 'Dutch social enterprise', 'https://fairphone.com'],
        ['Cat', 'American heavy equipment manufacturer', 'https://cat.com'],
        ['Ulefone', 'Chinese smartphone manufacturer', 'https://ulefone.com'],
        ['Doogee', 'Chinese smartphone manufacturer', 'https://doogee.cc'],
        ['Blackview', 'Chinese rugged smartphone manufacturer', 'https://blackview.hk'],
        ['Cubot', 'Chinese smartphone manufacturer', 'https://cubot.net'],
        ['Oukitel', 'Chinese smartphone manufacturer', 'https://oukitel.com'],
        ['AGM', 'Chinese rugged phone manufacturer', 'https://agmmobile.com'],
        ['Unihertz', 'Chinese smartphone manufacturer', 'https://unihertz.com'],
        ['Essential', 'American smartphone company', 'https://essential.com'],
        ['Vertu', 'British luxury mobile phone manufacturer', 'https://vertu.com'],
        ['Palm', 'American mobile device company', 'https://palm.com']
    ];

    $stmt = $pdo->prepare("INSERT INTO brands (name, description, website) VALUES (?, ?, ?) ON CONFLICT (name) DO NOTHING");

    $added = 0;
    foreach ($brands as $brand) {
        try {
            $stmt->execute($brand);
            if ($stmt->rowCount() > 0) {
                $added++;
                echo "Added brand: {$brand[0]}\n";
            } else {
                echo "Brand already exists: {$brand[0]}\n";
            }
        } catch (PDOException $e) {
            echo "Error adding brand {$brand[0]}: " . $e->getMessage() . "\n";
        }
    }

    echo "Total brands added: $added\n\n";
    return $added;
}

/**
 * Add 20 chipset instances to the database
 */
function addChipsets()
{
    $pdo = getDatabaseConnection();

    $chipsets = [
        ['Snapdragon 8 Gen 3', 'Qualcomm\'s latest flagship mobile platform', 'Qualcomm', 'ARM64', 8, 3.30],
        ['Apple A17 Pro', 'Apple\'s most powerful chip for iPhone', 'Apple', 'ARM64', 6, 3.78],
        ['Dimensity 9300', 'MediaTek\'s flagship mobile platform', 'MediaTek', 'ARM64', 8, 3.25],
        ['Exynos 2400', 'Samsung\'s flagship mobile processor', 'Samsung', 'ARM64', 10, 3.21],
        ['Tensor G3', 'Google\'s custom mobile processor', 'Google', 'ARM64', 9, 2.91],
        ['Snapdragon 8 Gen 2', 'Previous generation flagship from Qualcomm', 'Qualcomm', 'ARM64', 8, 3.19],
        ['Snapdragon 7+ Gen 2', 'Premium mid-range platform', 'Qualcomm', 'ARM64', 8, 2.91],
        ['Helio G99', 'Gaming-focused processor', 'MediaTek', 'ARM64', 8, 2.20],
        ['Snapdragon 695 5G', 'Mid-range 5G chipset', 'Qualcomm', 'ARM64', 8, 2.20],
        ['Dimensity 8050', 'Upper mid-range chipset', 'MediaTek', 'ARM64', 8, 3.00],
        ['Snapdragon 4 Gen 2', 'Entry-level 5G chipset', 'Qualcomm', 'ARM64', 8, 2.20],
        ['Helio G85', 'Gaming chipset for budget phones', 'MediaTek', 'ARM64', 8, 2.00],
        ['Unisoc Tiger T606', 'Entry-level processor', 'Unisoc', 'ARM64', 8, 1.60],
        ['Kirin 9000S', 'Huawei\'s flagship chipset', 'HiSilicon', 'ARM64', 8, 2.62],
        ['Snapdragon 6 Gen 1', 'Mid-range mobile platform', 'Qualcomm', 'ARM64', 8, 2.20],
        ['Dimensity 6080', 'Efficient mid-range chipset', 'MediaTek', 'ARM64', 8, 2.40],
        ['Helio P35', 'Budget-friendly processor', 'MediaTek', 'ARM64', 8, 2.30],
        ['Snapdragon 480 5G', 'Budget 5G chipset', 'Qualcomm', 'ARM64', 8, 2.00],
        ['Exynos 1380', 'Mid-range Samsung processor', 'Samsung', 'ARM64', 8, 2.40],
        ['Apple A16 Bionic', 'Previous generation Apple chip', 'Apple', 'ARM64', 6, 3.46]
    ];

    $stmt = $pdo->prepare("INSERT INTO chipsets (name, description, manufacturer, architecture, cores, frequency) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (name) DO NOTHING");

    $added = 0;
    foreach ($chipsets as $chipset) {
        try {
            $stmt->execute($chipset);
            if ($stmt->rowCount() > 0) {
                $added++;
                echo "Added chipset: {$chipset[0]}\n";
            } else {
                echo "Chipset already exists: {$chipset[0]}\n";
            }
        } catch (PDOException $e) {
            echo "Error adding chipset {$chipset[0]}: " . $e->getMessage() . "\n";
        }
    }

    echo "Total chipsets added: $added\n\n";
    return $added;
}

// Execute the data insertion
echo "Database Population Script\n";
echo "==========================\n\n";

echo "Adding brands...\n";
$brandsAdded = addBrands();

echo "Adding chipsets...\n";
$chipsetsAdded = addChipsets();

echo "Summary:\n";
echo "- Brands added: $brandsAdded\n";
echo "- Chipsets added: $chipsetsAdded\n";
echo "\nScript completed successfully!\n";
