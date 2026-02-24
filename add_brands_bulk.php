<?php
/**
 * Script to add 34 popular mobile phone brands to the database
 * Run this script once: php add_brands_bulk.php
 */

require_once 'database_functions.php';

// List of 34 popular mobile phone brands
$brands = [
    [
        'name' => 'Apple',
        'description' => 'American technology company known for iPhone and iPad devices.',
        'logo_url' => 'https://www.apple.com/favicon.ico',
        'website' => 'https://www.apple.com'
    ],
    [
        'name' => 'Samsung',
        'description' => 'South Korean multinational conglomerate producing Galaxy series smartphones.',
        'logo_url' => 'https://www.samsung.com/favicon.ico',
        'website' => 'https://www.samsung.com'
    ],
    [
        'name' => 'Xiaomi',
        'description' => 'Chinese electronics company known for affordable smartphones and Mi brand.',
        'logo_url' => 'https://www.mi.com/favicon.ico',
        'website' => 'https://www.mi.com'
    ],
    [
        'name' => 'OPPO',
        'description' => 'Chinese smartphone manufacturer focusing on camera and design innovation.',
        'logo_url' => 'https://www.oppo.com/favicon.ico',
        'website' => 'https://www.oppo.com'
    ],
    [
        'name' => 'Vivo',
        'description' => 'Chinese technology company specializing in smartphones and mobile accessories.',
        'logo_url' => 'https://www.vivo.com/favicon.ico',
        'website' => 'https://www.vivo.com'
    ],
    [
        'name' => 'Realme',
        'description' => 'Chinese smartphone brand offering budget-friendly devices with powerful features.',
        'logo_url' => 'https://www.realme.com/favicon.ico',
        'website' => 'https://www.realme.com'
    ],
    [
        'name' => 'Huawei',
        'description' => 'Chinese technology company producing smartphones and telecommunications equipment.',
        'logo_url' => 'https://www.huawei.com/favicon.ico',
        'website' => 'https://www.huawei.com'
    ],
    [
        'name' => 'OnePlus',
        'description' => 'Chinese smartphone manufacturer known for flagship killer devices.',
        'logo_url' => 'https://www.oneplus.com/favicon.ico',
        'website' => 'https://www.oneplus.com'
    ],
    [
        'name' => 'Google',
        'description' => 'American tech giant producing Pixel series smartphones with pure Android.',
        'logo_url' => 'https://www.google.com/favicon.ico',
        'website' => 'https://www.google.com/phones'
    ],
    [
        'name' => 'Motorola',
        'description' => 'American smartphone manufacturer known for Moto G and Razr series.',
        'logo_url' => 'https://www.motorola.com/favicon.ico',
        'website' => 'https://www.motorola.com'
    ],
    [
        'name' => 'Nokia',
        'description' => 'Finnish technology company with history of mobile phones.',
        'logo_url' => 'https://www.nokia.com/favicon.ico',
        'website' => 'https://www.nokia.com'
    ],
    [
        'name' => 'Sony',
        'description' => 'Japanese conglomerate producing Xperia series smartphones.',
        'logo_url' => 'https://www.sony.com/favicon.ico',
        'website' => 'https://www.sony.com'
    ],
    [
        'name' => 'LG',
        'description' => 'South Korean company that produced LG G series smartphones.',
        'logo_url' => 'https://www.lg.com/favicon.ico',
        'website' => 'https://www.lg.com'
    ],
    [
        'name' => 'BlackBerry',
        'description' => 'Canadian smartphone company known for secure communication devices.',
        'logo_url' => 'https://www.blackberry.com/favicon.ico',
        'website' => 'https://www.blackberry.com'
    ],
    [
        'name' => 'HTC',
        'description' => 'Taiwanese smartphone manufacturer known for sense UI customization.',
        'logo_url' => 'https://www.htc.com/favicon.ico',
        'website' => 'https://www.htc.com'
    ],
    [
        'name' => 'Lenovo',
        'description' => 'Chinese multinational technology company producing Moto and Lenovo phones.',
        'logo_url' => 'https://www.lenovo.com/favicon.ico',
        'website' => 'https://www.lenovo.com'
    ],
    [
        'name' => 'ASUS',
        'description' => 'Taiwanese company producing gaming and ROG series smartphones.',
        'logo_url' => 'https://www.asus.com/favicon.ico',
        'website' => 'https://www.asus.com'
    ],
    [
        'name' => 'MSI',
        'description' => 'Taiwanese electronics company known for gaming devices.',
        'logo_url' => 'https://www.msi.com/favicon.ico',
        'website' => 'https://www.msi.com'
    ],
    [
        'name' => 'Razer',
        'description' => 'American company specializing in gaming hardware and accessories.',
        'logo_url' => 'https://www.razer.com/favicon.ico',
        'website' => 'https://www.razer.com'
    ],
    [
        'name' => 'Nothing',
        'description' => 'UK technology company known for innovative design and Nothing Phone.',
        'logo_url' => 'https://www.nothing.tech/favicon.ico',
        'website' => 'https://www.nothing.tech'
    ],
    [
        'name' => 'Honor',
        'description' => 'Chinese technology brand offering affordable smartphones with cutting-edge features.',
        'logo_url' => 'https://www.honor.com/favicon.ico',
        'website' => 'https://www.honor.com'
    ],
    [
        'name' => 'ZTE',
        'description' => 'Chinese multinational telecommunications equipment and smartphone manufacturer.',
        'logo_url' => 'https://www.zte.com.cn/favicon.ico',
        'website' => 'https://www.zte.com.cn'
    ],
    [
        'name' => 'Ulefone',
        'description' => 'Chinese rugged smartphone manufacturer.',
        'logo_url' => 'https://www.ulefone.com/favicon.ico',
        'website' => 'https://www.ulefone.com'
    ],
    [
        'name' => 'AGM',
        'description' => 'Chinese company specializing in rugged and waterproof smartphones.',
        'logo_url' => 'https://www.agmobil.com/favicon.ico',
        'website' => 'https://www.agmobil.com'
    ],
    [
        'name' => 'Blackview',
        'description' => 'Chinese manufacturer of rugged and durable mobile phones.',
        'logo_url' => 'https://www.blackviewmobile.com/favicon.ico',
        'website' => 'https://www.blackviewmobile.com'
    ],
    [
        'name' => 'Doogee',
        'description' => 'Chinese smartphone manufacturer known for rugged devices.',
        'logo_url' => 'https://www.doogee.cc/favicon.ico',
        'website' => 'https://www.doogee.cc'
    ],
    [
        'name' => 'Oukitel',
        'description' => 'Chinese company producing rugged and budget smartphones.',
        'logo_url' => 'https://www.oukitel.com/favicon.ico',
        'website' => 'https://www.oukitel.com'
    ],
    [
        'name' => 'Umidigi',
        'description' => 'Chinese smartphone manufacturer offering affordable and practical devices.',
        'logo_url' => 'https://www.umidigi.com/favicon.ico',
        'website' => 'https://www.umidigi.com'
    ],
    [
        'name' => 'Cubot',
        'description' => 'Chinese electronics company specializing in affordable smartphones.',
        'logo_url' => 'https://www.cubot.net/favicon.ico',
        'website' => 'https://www.cubot.net'
    ],
    [
        'name' => 'Leagoo',
        'description' => 'Chinese technology company producing budget-friendly smartphones.',
        'logo_url' => 'https://www.leagoo.com/favicon.ico',
        'website' => 'https://www.leagoo.com'
    ],
    [
        'name' => 'Vernee',
        'description' => 'Chinese smartphone manufacturer offering affordable devices.',
        'logo_url' => 'https://www.vernee.com/favicon.ico',
        'website' => 'https://www.vernee.com'
    ],
    [
        'name' => 'Elephone',
        'description' => 'Chinese company producing budget-friendly smartphones.',
        'logo_url' => 'https://www.elephone.com/favicon.ico',
        'website' => 'https://www.elephone.com'
    ],
    [
        'name' => 'Homtom',
        'description' => 'Chinese budget smartphone manufacturer.',
        'logo_url' => 'https://www.homtom.com/favicon.ico',
        'website' => 'https://www.homtom.com'
    ],
    [
        'name' => 'Mixc',
        'description' => 'Chinese smartphone brand offering value for money devices.',
        'logo_url' => 'https://www.mixc.com/favicon.ico',
        'website' => 'https://www.mixc.com'
    ]
];

try {
    $pdo = getConnection();
    $added_count = 0;
    $skipped_count = 0;

    echo "Starting bulk brand insertion...\n";
    echo "Total brands to insert: " . count($brands) . "\n\n";

    foreach ($brands as $brand) {
        // Check if brand already exists
        $check_stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
        $check_stmt->execute([$brand['name']]);

        if ($check_stmt->fetch()) {
            echo "✓ Skipped: {$brand['name']} (already exists)\n";
            $skipped_count++;
            continue;
        }

        // Insert the brand
        $insert_stmt = $pdo->prepare("
            INSERT INTO brands (name, description, logo_url, website, created_at, updated_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");

        $insert_stmt->execute([
            $brand['name'],
            $brand['description'],
            $brand['logo_url'],
            $brand['website']
        ]);

        echo "✓ Added: {$brand['name']}\n";
        $added_count++;
    }

    echo "\n=== SUMMARY ===\n";
    echo "Successfully added: {$added_count} brands\n";
    echo "Skipped (already existed): {$skipped_count} brands\n";
    echo "Total processed: " . ($added_count + $skipped_count) . " brands\n";
    echo "\n✓ Bulk brand insertion completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
