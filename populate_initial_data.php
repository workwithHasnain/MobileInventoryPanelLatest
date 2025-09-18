<?php
require_once 'config.php';
require_once 'database_functions.php';

// Ensure database connection
$db_conn = getConnection();
if (!$db_conn) {
    die("Database connection failed");
}

try {
    // First truncate all tables in the correct order (due to foreign key constraints)
    $truncateQueries = [
        'TRUNCATE TABLE device_comparisons CASCADE',
        'TRUNCATE TABLE device_views CASCADE',
        'TRUNCATE TABLE device_reviews CASCADE',
        'TRUNCATE TABLE device_comments CASCADE',
        'TRUNCATE TABLE post_comments CASCADE',
        'TRUNCATE TABLE phones CASCADE',
        'TRUNCATE TABLE posts CASCADE',
        'TRUNCATE TABLE post_categories CASCADE',
        'TRUNCATE TABLE chipsets CASCADE',
        'TRUNCATE TABLE brands CASCADE'
    ];

    foreach ($truncateQueries as $query) {
        $result = $db_conn->exec($query);
        if ($result === false) {
            throw new Exception("Failed to execute truncate query: " . implode(", ", $db_conn->errorInfo()));
        }
    }

    // Prepare data arrays
    $brands = [
        ['Samsung', 'South Korean multinational electronics company', 'samsung_logo.png', 'https://www.samsung.com'],
        ['Apple', 'American technology company', 'apple_logo.png', 'https://www.apple.com'],
        ['Xiaomi', 'Chinese electronics company', 'xiaomi_logo.png', 'https://www.mi.com'],
        ['OnePlus', 'Chinese smartphone manufacturer', 'oneplus_logo.png', 'https://www.oneplus.com'],
        ['OPPO', 'Chinese consumer electronics manufacturer', 'oppo_logo.png', 'https://www.oppo.com'],
        ['Vivo', 'Chinese technology company', 'vivo_logo.png', 'https://www.vivo.com'],
        ['Huawei', 'Chinese multinational technology company', 'huawei_logo.png', 'https://www.huawei.com'],
        ['Google', 'American technology company', 'google_logo.png', 'https://store.google.com'],
        ['Sony', 'Japanese multinational corporation', 'sony_logo.png', 'https://www.sony.com'],
        ['LG', 'South Korean multinational company', 'lg_logo.png', 'https://www.lg.com'],
        ['Motorola', 'American telecommunications company', 'motorola_logo.png', 'https://www.motorola.com'],
        ['Nokia', 'Finnish multinational telecommunications company', 'nokia_logo.png', 'https://www.nokia.com'],
        ['ASUS', 'Taiwanese multinational computer company', 'asus_logo.png', 'https://www.asus.com'],
        ['Lenovo', 'Chinese multinational technology company', 'lenovo_logo.png', 'https://www.lenovo.com'],
        ['realme', 'Chinese smartphone manufacturer', 'realme_logo.png', 'https://www.realme.com'],
        ['ZTE', 'Chinese telecommunications equipment company', 'zte_logo.png', 'https://www.zte.com'],
        ['HTC', 'Taiwanese consumer electronics company', 'htc_logo.png', 'https://www.htc.com'],
        ['BlackBerry', 'Canadian software company', 'blackberry_logo.png', 'https://www.blackberry.com'],
        ['Honor', 'Chinese smartphone brand', 'honor_logo.png', 'https://www.hihonor.com'],
        ['Meizu', 'Chinese consumer electronics company', 'meizu_logo.png', 'https://www.meizu.com'],
        ['TCL', 'Chinese electronics company', 'tcl_logo.png', 'https://www.tcl.com'],
        ['Sharp', 'Japanese corporation', 'sharp_logo.png', 'https://www.sharp.com'],
        ['Infinix', 'Hong Kong-based smartphone maker', 'infinix_logo.png', 'https://www.infinixmobility.com'],
        ['Tecno', 'Chinese mobile phone manufacturer', 'tecno_logo.png', 'https://www.tecno-mobile.com'],
        ['iQOO', 'Chinese smartphone brand', 'iqoo_logo.png', 'https://www.iqoo.com'],
        ['Microsoft', 'American technology corporation', 'microsoft_logo.png', 'https://www.microsoft.com'],
        ['Nothing', 'London-based consumer technology company', 'nothing_logo.png', 'https://nothing.tech'],
        ['Fairphone', 'Social enterprise smartphone manufacturer', 'fairphone_logo.png', 'https://www.fairphone.com'],
        ['CAT', 'Rugged phone manufacturer', 'cat_logo.png', 'https://www.catphones.com'],
        ['Poco', 'Xiaomi sub-brand', 'poco_logo.png', 'https://www.poco.net'],
        ['RedMagic', 'Gaming phone brand by Nubia', 'redmagic_logo.png', 'https://global.redmagic.gg'],
        ['ROG', 'ASUS Republic of Gamers', 'rog_logo.png', 'https://rog.asus.com'],
        ['Red Hydrogen', 'Professional camera company', 'red_logo.png', 'https://www.red.com'],
        ['Palm', 'American mobile device manufacturer', 'palm_logo.png', 'https://palm.com'],
        ['Razer', 'Gaming hardware manufacturer', 'razer_logo.png', 'https://www.razer.com'],
        ['Essential', 'American technology company', 'essential_logo.png', 'https://www.essential.com']
    ];

    $chipsets = [
        ['Snapdragon 8 Gen 3', 'Qualcomm', 'ARM', 8, 3.30],
        ['A17 Pro', 'Apple', 'ARM', 6, 3.78],
        ['Dimensity 9300', 'MediaTek', 'ARM', 8, 3.25],
        ['Exynos 2400', 'Samsung', 'ARM', 10, 3.20],
        ['Google Tensor G4', 'Google', 'ARM', 8, 2.91],
        ['Snapdragon 8+ Gen 2', 'Qualcomm', 'ARM', 8, 3.20],
        ['A16 Bionic', 'Apple', 'ARM', 6, 3.46],
        ['Dimensity 9200+', 'MediaTek', 'ARM', 8, 3.35],
        ['Snapdragon 7+ Gen 2', 'Qualcomm', 'ARM', 8, 2.91],
        ['Dimensity 8300', 'MediaTek', 'ARM', 8, 3.00],
        ['Exynos 1380', 'Samsung', 'ARM', 8, 2.40],
        ['Snapdragon 6 Gen 1', 'Qualcomm', 'ARM', 8, 2.20],
        ['Helio G99', 'MediaTek', 'ARM', 8, 2.20],
        ['Snapdragon 4 Gen 2', 'Qualcomm', 'ARM', 8, 2.00],
        ['Dimensity 7050', 'MediaTek', 'ARM', 8, 2.60],
        ['Google Tensor G3', 'Google', 'ARM', 8, 2.80],
        ['Snapdragon 8 Gen 2', 'Qualcomm', 'ARM', 8, 3.20],
        ['A15 Bionic', 'Apple', 'ARM', 6, 3.36],
        ['Dimensity 9200', 'MediaTek', 'ARM', 8, 3.05],
        ['Snapdragon 7 Gen 1', 'Qualcomm', 'ARM', 8, 2.40]
    ];

    $postCategories = [
        ['News', 'Latest updates and announcements in the mobile industry'],
        ['Reviews', 'In-depth analysis and reviews of mobile devices'],
        ['Comparisons', 'Side-by-side comparisons of different devices'],
        ['Tips & Tricks', 'Helpful tips and hidden features for mobile devices'],
        ['Technology', 'General technology news and innovations'],
        ['Accessories', 'Mobile accessories and peripherals'],
        ['Software Updates', 'OS updates and software-related news'],
        ['Buying Guides', 'Guides to help users make informed purchase decisions'],
        ['Gaming', 'Mobile gaming news and reviews'],
        ['Security', 'Mobile security and privacy related content']
    ];

    // Insert brands
    $brandInsertQuery = $db_conn->prepare('INSERT INTO brands (name, description, logo_url, website) VALUES (?, ?, ?, ?)');
    foreach ($brands as $brand) {
        $result = $brandInsertQuery->execute($brand);
        if (!$result) {
            throw new Exception("Failed to insert brand: " . implode(", ", $brandInsertQuery->errorInfo()));
        }
    }
    echo "Successfully inserted " . count($brands) . " brands\n";

    // Insert chipsets
    $chipsetInsertQuery = $db_conn->prepare('INSERT INTO chipsets (name, manufacturer, architecture, cores, frequency) VALUES (?, ?, ?, ?, ?)');
    foreach ($chipsets as $chipset) {
        $result = $chipsetInsertQuery->execute($chipset);
        if (!$result) {
            throw new Exception("Failed to insert chipset: " . implode(", ", $chipsetInsertQuery->errorInfo()));
        }
    }
    echo "Successfully inserted " . count($chipsets) . " chipsets\n";

    // Insert post categories
    $categoryInsertQuery = $db_conn->prepare('INSERT INTO post_categories (name, description) VALUES (?, ?)');
    foreach ($postCategories as $category) {
        $result = $categoryInsertQuery->execute($category);
        if (!$result) {
            throw new Exception("Failed to insert category: " . implode(", ", $categoryInsertQuery->errorInfo()));
        }
    }
    echo "Successfully inserted " . count($postCategories) . " post categories\n";

    echo "\nAll data inserted successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // PDO connection will be closed automatically when the script ends
    $db_conn = null;
}
