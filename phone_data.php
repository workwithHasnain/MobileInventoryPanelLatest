<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'database_functions.php';

/**
 * Get all phones from the database
 * 
 * @return array Array of phone data
 */
function getAllPhones()
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("
            SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            ORDER BY p.name
        ");
        $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format phones to match expected structure
        foreach ($phones as &$phone) {
            $phone['brand'] = $phone['brand_name'];
            $phone['chipset'] = $phone['chipset_name'];

            // Decode PostgreSQL arrays back to PHP arrays
            $arrayFields = [
                'images',
                'network_2g',
                'network_3g',
                'network_4g',
                'network_5g',
                'sim_size',
                'ip_certificate',
                'wifi',
                'bluetooth',
                'main_camera_features',
                'selfie_camera_features'
            ];

            foreach ($arrayFields as $field) {
                if (isset($phone[$field]) && is_string($phone[$field])) {
                    // Handle PostgreSQL array format: {value1,value2,value3}
                    if (strpos($phone[$field], '{') === 0 && strpos($phone[$field], '}') === strlen($phone[$field]) - 1) {
                        $phone[$field] = array_filter(explode(',', trim($phone[$field], '{}')));
                        // Clean up any empty values
                        $phone[$field] = array_values(array_filter($phone[$field], function ($v) {
                            return !empty(trim($v));
                        }));
                    }
                }
            }

            // Map database field names to form field names for compatibility
            $phone['2g'] = $phone['network_2g'] ?? [];
            $phone['3g'] = $phone['network_3g'] ?? [];
            $phone['4g'] = $phone['network_4g'] ?? [];
            $phone['5g'] = $phone['network_5g'] ?? [];
        }

        return $phones;
    } catch (Exception $e) {
        error_log('Error getting phones: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add a new phone to the database
 * 
 * @param array $phone Phone data
 * @return array|bool Returns true on success, array with error on failure
 */
function addPhone($phone)
{
    try {
        $pdo = getConnection();

        // Validate required fields
        if (empty(trim($phone['name'] ?? ''))) {
            return ['error' => 'Phone name is required'];
        }

        // Get or create brand and get brand_id
        $brand_id = null;
        if (!empty($phone['brand'])) {
            // First try to get existing brand
            $stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([trim($phone['brand'])]);
            $brand_id = $stmt->fetchColumn();

            // If brand doesn't exist, create it
            if (!$brand_id) {
                $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?) RETURNING id");
                $stmt->execute([trim($phone['brand'])]);
                $brand_id = $stmt->fetchColumn();
            }
        }

        if (!$brand_id) {
            return ['error' => 'Brand is required'];
        }

        // Get chipset ID if provided
        $chipset_id = null;
        if (!empty($phone['chipset'])) {
            $stmt = $pdo->prepare("SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([trim($phone['chipset'])]);
            $chipset_id = $stmt->fetchColumn();
        }

        // Check for exact duplicate device
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE LOWER(name) = LOWER(?) AND brand_id = ?");
        $stmt->execute([trim($phone['name']), $brand_id]);
        if ($stmt->fetchColumn() > 0) {
            return ['error' => 'A device with this name and brand already exists'];
        }

        // Build the insert query with all available fields
        $stmt = $pdo->prepare(
            "
            INSERT INTO phones (
                name, brand_id, brand, chipset_id, availability, price, year,
                release_date, image, images, 
                -- Network
                network_2g, network_3g, network_4g, network_5g,
                dual_sim, esim, sim_size,
                -- Body
                dimensions, form_factor, keyboard, height, width, thickness, weight,
                ip_certificate, color, back_material, frame_material,
                -- Display
                display_type, display_size, display_resolution, display_density,
                display_technology, display_notch, refresh_rate, hdr, billion_colors,
                -- Platform
                os, os_version, cpu_cores,
                -- Memory
                ram, storage, card_slot,
                -- Camera
                main_camera_count, main_camera_resolution, main_camera_features,
                main_camera_video, main_camera_ois, main_camera_telephoto,
                main_camera_ultrawide, main_camera_flash, main_camera_f_number,
                selfie_camera_count, selfie_camera_resolution, selfie_camera_features,
                selfie_camera_video, selfie_camera_ois, selfie_camera_flash,
                popup_camera, under_display_camera,
                -- Audio
                headphone_jack, dual_speakers,
                -- Communications
                wifi, bluetooth, gps, nfc, infrared, fm_radio, usb,
                -- Sensors
                accelerometer, gyro, compass, proximity, barometer,
                heart_rate, fingerprint,
                -- Battery
                battery_capacity, battery_sic, battery_removable,
                wired_charging, wireless_charging
            ) VALUES (
                /* Basic Info: 10 */
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                /* Network: 7 */
                ?, ?, ?, ?, ?, ?, ?,
                /* Body: 11 */
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                /* Display: 9 */
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                /* Platform: 3 */
                ?, ?, ?,
                /* Memory: 3 */
                ?, ?, ?,
                /* Camera: 17 */
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                /* Audio: 2 */
                ?, ?,
                /* Communications: 7 */
                ?, ?, ?, ?, ?, ?, ?,
                /* Sensors: 7 */
                ?, ?, ?, ?, ?, ?, ?,
                /* Battery: 5 */
                ?, ?, ?, ?, ?
            )"
        );

        // Helper function to convert numeric values
        $toNumber = function ($value, $type = 'float') {
            if (empty($value) || !is_numeric($value)) {
                return null;
            }
            return $type === 'int' ? intval($value) : floatval($value);
        };

        // Helper function to convert array to PostgreSQL format
        $toPostgresArray = function ($arr) {
            if (empty($arr)) return null;
            if (!is_array($arr)) $arr = [$arr];
            return '{' . implode(',', array_map('trim', $arr)) . '}';
        };

        // Prepare values with proper type conversion
        $values = [
            // Basic Info
            trim($phone['name'] ?? ''),               // name
            $brand_id,                                // brand_id
            trim($phone['brand'] ?? ''),              // brand
            $chipset_id,                             // chipset_id
            trim($phone['availability'] ?? ''),       // availability
            $toNumber($phone['price'] ?? ''),        // price
            $toNumber($phone['year'] ?? '', 'int'),  // year
            $phone['release_date'] ?? null,           // release_date
            $phone['image'] ?? null,                  // image
            $toPostgresArray($phone['images'] ?? []), // images array

            // Network
            $toPostgresArray($phone['2g'] ?? []),     // network_2g array
            $toPostgresArray($phone['3g'] ?? []),     // network_3g array
            $toPostgresArray($phone['4g'] ?? []),     // network_4g array
            $toPostgresArray($phone['5g'] ?? []),     // network_5g array
            ($phone['dual_sim'] === true || $phone['dual_sim'] === 'true' || $phone['dual_sim'] === '1') ? 'true' : 'false',   // dual_sim
            ($phone['esim'] === true || $phone['esim'] === 'true' || $phone['esim'] === '1') ? 'true' : 'false',       // esim
            $toPostgresArray($phone['sim_size'] ?? []), // sim_size array

            // Body
            trim($phone['dimensions'] ?? ''),         // dimensions
            trim($phone['form_factor'] ?? ''),       // form_factor
            trim($phone['keyboard'] ?? ''),          // keyboard
            $toNumber($phone['height'] ?? ''),        // height
            $toNumber($phone['width'] ?? ''),         // width
            $toNumber($phone['thickness'] ?? ''),     // thickness
            $toNumber($phone['weight'] ?? ''),        // weight
            $toPostgresArray($phone['ip_certificate'] ?? []), // ip_certificate array
            trim($phone['color'] ?? ''),             // color
            trim($phone['back_material'] ?? ''),     // back_material
            trim($phone['frame_material'] ?? ''),    // frame_material

            // Display
            $phone['display_type'] ?? null,           // display_type
            $toNumber($phone['display_size'] ?? ''),  // display_size
            $phone['display_resolution'] ?? null,      // display_resolution
            $phone['display_density'] ?? null,        // display_density
            $phone['display_technology'] ?? null,     // display_technology
            $phone['display_notch'] ?? null,          // display_notch
            $phone['refresh_rate'] ?? null,          // refresh_rate
            ($phone['hdr'] === true || $phone['hdr'] === 'true' || $phone['hdr'] === '1') ? 'true' : 'false',                    // hdr
            ($phone['billion_colors'] === true || $phone['billion_colors'] === 'true' || $phone['billion_colors'] === '1') ? 'true' : 'false',         // billion_colors

            // Platform
            $phone['os'] ?? null,                    // os
            $phone['os_version'] ?? null,            // os_version
            $phone['cpu_cores'] ?? null,             // cpu_cores

            // Memory
            $phone['ram'] ?? null,                   // ram
            $phone['storage'] ?? null,               // storage
            $phone['card_slot'] ?? null,            // card_slot

            // Main Camera
            $toNumber($phone['main_camera_count'] ?? '', 'int'),  // main_camera_count
            $phone['main_camera_resolution'] ?? null,             // main_camera_resolution
            $toPostgresArray($phone['main_camera_features'] ?? []), // main_camera_features array
            $phone['main_camera_video'] ?? null,                  // main_camera_video
            ($phone['main_camera_ois'] === true || $phone['main_camera_ois'] === 'true' || $phone['main_camera_ois'] === '1') ? 'true' : 'false',                    // main_camera_ois
            ($phone['main_camera_telephoto'] === true || $phone['main_camera_telephoto'] === 'true' || $phone['main_camera_telephoto'] === '1') ? 'true' : 'false',              // main_camera_telephoto
            ($phone['main_camera_ultrawide'] === true || $phone['main_camera_ultrawide'] === 'true' || $phone['main_camera_ultrawide'] === '1') ? 'true' : 'false',              // main_camera_ultrawide
            ($phone['main_camera_flash'] === true || $phone['main_camera_flash'] === 'true' || $phone['main_camera_flash'] === '1') ? 'true' : 'false',                  // main_camera_flash
            $phone['main_camera_f_number'] ?? null,              // main_camera_f_number

            // Selfie Camera
            $toNumber($phone['selfie_camera_count'] ?? '', 'int'), // selfie_camera_count
            $phone['selfie_camera_resolution'] ?? null,            // selfie_camera_resolution
            $toPostgresArray($phone['selfie_camera_features'] ?? []), // selfie_camera_features array
            $phone['selfie_camera_video'] ?? null,                // selfie_camera_video
            ($phone['selfie_camera_ois'] === true || $phone['selfie_camera_ois'] === 'true' || $phone['selfie_camera_ois'] === '1') ? 'true' : 'false',                  // selfie_camera_ois
            ($phone['selfie_camera_flash'] === true || $phone['selfie_camera_flash'] === 'true' || $phone['selfie_camera_flash'] === '1') ? 'true' : 'false',                // selfie_camera_flash
            ($phone['popup_camera'] === true || $phone['popup_camera'] === 'true' || $phone['popup_camera'] === '1') ? 'true' : 'false',                       // popup_camera
            ($phone['under_display_camera'] === true || $phone['under_display_camera'] === 'true' || $phone['under_display_camera'] === '1') ? 'true' : 'false',               // under_display_camera

            // Audio
            ($phone['headphone_jack'] === true || $phone['headphone_jack'] === 'true' || $phone['headphone_jack'] === '1') ? 'true' : 'false',                     // headphone_jack
            ($phone['dual_speakers'] === true || $phone['dual_speakers'] === 'true' || $phone['dual_speakers'] === '1') ? 'true' : 'false',                      // dual_speakers

            // Communications
            $toPostgresArray($phone['wifi'] ?? []),              // wifi array
            $toPostgresArray($phone['bluetooth'] ?? []),         // bluetooth array
            ($phone['gps'] === true || $phone['gps'] === 'true' || $phone['gps'] === '1') ? 'true' : 'false',                               // gps
            ($phone['nfc'] === true || $phone['nfc'] === 'true' || $phone['nfc'] === '1') ? 'true' : 'false',                               // nfc
            ($phone['infrared'] === true || $phone['infrared'] === 'true' || $phone['infrared'] === '1') ? 'true' : 'false',                          // infrared
            ($phone['fm_radio'] === true || $phone['fm_radio'] === 'true' || $phone['fm_radio'] === '1') ? 'true' : 'false',                          // fm_radio
            $phone['usb'] ?? null,                              // usb

            // Sensors
            ($phone['accelerometer'] === true || $phone['accelerometer'] === 'true' || $phone['accelerometer'] === '1') ? 'true' : 'false',   // accelerometer
            ($phone['gyro'] === true || $phone['gyro'] === 'true' || $phone['gyro'] === '1') ? 'true' : 'false',           // gyro
            ($phone['compass'] === true || $phone['compass'] === 'true' || $phone['compass'] === '1') ? 'true' : 'false',        // compass
            ($phone['proximity'] === true || $phone['proximity'] === 'true' || $phone['proximity'] === '1') ? 'true' : 'false',      // proximity
            ($phone['barometer'] === true || $phone['barometer'] === 'true' || $phone['barometer'] === '1') ? 'true' : 'false',      // barometer
            ($phone['heart_rate'] === true || $phone['heart_rate'] === 'true' || $phone['heart_rate'] === '1') ? 'true' : 'false',     // heart_rate
            trim($phone['fingerprint'] ?? ''),                 // fingerprint

            // Battery
            $toNumber($phone['battery_capacity'] ?? '', 'int'), // battery_capacity
            ($phone['battery_sic'] === true || $phone['battery_sic'] === 'true' || $phone['battery_sic'] === '1') ? 'true' : 'false',    // battery_sic
            ($phone['battery_removable'] === true || $phone['battery_removable'] === 'true' || $phone['battery_removable'] === '1') ? 'true' : 'false', // battery_removable
            trim($phone['wired_charging'] ?? ''),              // wired_charging
            trim($phone['wireless_charging'] ?? '')            // wireless_charging
        ];

        $result = $stmt->execute($values);

        if ($result) {
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log('Database error when adding phone: ' . json_encode($errorInfo));
            return ['error' => 'Database error: ' . $errorInfo[2]];
        }
    } catch (Exception $e) {
        error_log('Error adding phone: ' . $e->getMessage());
        return ['error' => 'System error: ' . $e->getMessage()];
    }
}

/**
 * Update an existing phone
 * 
 * @param int $id Phone ID
 * @param array $phone Updated phone data
 * @return bool Success status
 */
function updatePhone($id, $phone)
{
    try {
        $pdo = getConnection();

        // Helper function to convert array to PostgreSQL format
        $toPostgresArray = function ($arr) {
            if (empty($arr)) return null;
            if (!is_array($arr)) $arr = [$arr];
            return '{' . implode(',', array_map('trim', $arr)) . '}';
        };

        // Helper function to convert numeric values
        $toNumber = function ($value, $type = 'float') {
            if (empty($value) || !is_numeric($value)) {
                return null;
            }
            return $type === 'int' ? intval($value) : floatval($value);
        };

        // Get brand ID
        $brand_id = null;
        if (isset($phone['brand'])) {
            $stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$phone['brand']]);
            $brand_id = $stmt->fetchColumn();
        }

        // Get chipset ID
        $chipset_id = null;
        if (isset($phone['chipset'])) {
            $stmt = $pdo->prepare("SELECT id FROM chipsets WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$phone['chipset']]);
            $chipset_id = $stmt->fetchColumn();
        }

        // Update phone with all fields - matching the actual database schema
        $stmt = $pdo->prepare("
            UPDATE phones SET 
                name = ?, brand_id = ?, availability = ?, price = ?, year = ?, release_date = ?,
                image = ?, images = ?,
                -- Network
                network_2g = ?, network_3g = ?, network_4g = ?, network_5g = ?,
                dual_sim = ?, esim = ?, sim_size = ?,
                -- Body
                dimensions = ?, form_factor = ?, keyboard = ?, height = ?, width = ?, thickness = ?, weight = ?,
                ip_certificate = ?, color = ?, back_material = ?, frame_material = ?,
                -- Display
                display_type = ?, display_size = ?, display_resolution = ?, display_density = ?,
                display_technology = ?, display_notch = ?, refresh_rate = ?, hdr = ?, billion_colors = ?,
                -- Platform
                os = ?, os_version = ?, chipset_id = ?, cpu_cores = ?,
                -- Memory
                ram = ?, storage = ?, card_slot = ?,
                -- Main Camera
                main_camera_count = ?, main_camera_resolution = ?, main_camera_f_number = ?,
                main_camera_video = ?, main_camera_ois = ?, main_camera_telephoto = ?,
                main_camera_ultrawide = ?, main_camera_flash = ?,
                -- Selfie Camera
                selfie_camera_count = ?, selfie_camera_resolution = ?, selfie_camera_ois = ?,
                selfie_camera_flash = ?, popup_camera = ?, under_display_camera = ?,
                -- Audio
                headphone_jack = ?, dual_speakers = ?,
                -- Communications
                wifi = ?, bluetooth = ?, gps = ?, nfc = ?, infrared = ?, fm_radio = ?, usb = ?,
                -- Sensors
                accelerometer = ?, gyro = ?, compass = ?, proximity = ?, barometer = ?,
                heart_rate = ?, fingerprint = ?,
                -- Battery
                battery_capacity = ?, battery_sic = ?, battery_removable = ?,
                wired_charging = ?, wireless_charging = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $values = [
            // Basic Info
            trim($phone['name'] ?? ''),               // name
            $brand_id,                                // brand_id
            trim($phone['availability'] ?? ''),       // availability
            $toNumber($phone['price'] ?? ''),        // price
            $toNumber($phone['year'] ?? '', 'int'),  // year
            $phone['release_date'] ?? null,           // release_date
            $phone['image'] ?? null,                  // image
            $toPostgresArray($phone['images'] ?? []), // images array

            // Network
            $toPostgresArray($phone['2g'] ?? []),     // network_2g array
            $toPostgresArray($phone['3g'] ?? []),     // network_3g array
            $toPostgresArray($phone['4g'] ?? []),     // network_4g array
            $toPostgresArray($phone['5g'] ?? []),     // network_5g array
            ($phone['dual_sim'] === true || $phone['dual_sim'] === 'true' || $phone['dual_sim'] === '1') ? 'true' : 'false',   // dual_sim
            ($phone['esim'] === true || $phone['esim'] === 'true' || $phone['esim'] === '1') ? 'true' : 'false',       // esim
            $toPostgresArray($phone['sim_size'] ?? []), // sim_size array

            // Body
            trim($phone['dimensions'] ?? ''),         // dimensions
            trim($phone['form_factor'] ?? ''),       // form_factor
            trim($phone['keyboard'] ?? ''),          // keyboard
            $toNumber($phone['height'] ?? ''),        // height
            $toNumber($phone['width'] ?? ''),         // width
            $toNumber($phone['thickness'] ?? ''),     // thickness
            $toNumber($phone['weight'] ?? ''),        // weight
            $toPostgresArray($phone['ip_certificate'] ?? []), // ip_certificate array
            trim($phone['color'] ?? ''),             // color
            trim($phone['back_material'] ?? ''),     // back_material
            trim($phone['frame_material'] ?? ''),    // frame_material

            // Display
            $phone['display_type'] ?? null,           // display_type
            $toNumber($phone['display_size'] ?? ''),  // display_size
            $phone['display_resolution'] ?? null,      // display_resolution
            $toNumber($phone['display_density'] ?? '', 'int'), // display_density (INTEGER)
            $phone['display_technology'] ?? null,     // display_technology
            $phone['display_notch'] ?? null,          // display_notch
            $phone['refresh_rate'] ?? null,          // refresh_rate
            ($phone['hdr'] === true || $phone['hdr'] === 'true' || $phone['hdr'] === '1') ? 'true' : 'false',                    // hdr
            ($phone['billion_colors'] === true || $phone['billion_colors'] === 'true' || $phone['billion_colors'] === '1') ? 'true' : 'false',         // billion_colors

            // Platform
            $phone['os'] ?? null,                    // os
            $phone['os_version'] ?? null,            // os_version
            $chipset_id,                             // chipset_id
            $phone['cpu_cores'] ?? null,             // cpu_cores

            // Memory
            $toNumber($phone['ram'] ?? ''),          // ram (DECIMAL(5,1))
            $toNumber($phone['storage'] ?? '', 'int'), // storage (INTEGER)
            $phone['card_slot'] ?? null,            // card_slot

            // Main Camera
            $toNumber($phone['main_camera_count'] ?? '', 'int'),  // main_camera_count
            $toNumber($phone['main_camera_resolution'] ?? ''),    // main_camera_resolution (DECIMAL(5,1))
            $toNumber($phone['main_camera_f_number'] ?? ''),      // main_camera_f_number (DECIMAL(3,1))
            $phone['main_camera_video'] ?? null,                  // main_camera_video
            ($phone['main_camera_ois'] === true || $phone['main_camera_ois'] === 'true' || $phone['main_camera_ois'] === '1') ? 'true' : 'false',                    // main_camera_ois
            ($phone['main_camera_telephoto'] === true || $phone['main_camera_telephoto'] === 'true' || $phone['main_camera_telephoto'] === '1') ? 'true' : 'false',              // main_camera_telephoto
            ($phone['main_camera_ultrawide'] === true || $phone['main_camera_ultrawide'] === 'true' || $phone['main_camera_ultrawide'] === '1') ? 'true' : 'false',              // main_camera_ultrawide
            $phone['main_camera_flash'] ?? null,                  // main_camera_flash (VARCHAR(50))

            // Selfie Camera
            $toNumber($phone['selfie_camera_count'] ?? '', 'int'), // selfie_camera_count
            $toNumber($phone['selfie_camera_resolution'] ?? ''),   // selfie_camera_resolution (DECIMAL(5,1))
            ($phone['selfie_camera_ois'] === true || $phone['selfie_camera_ois'] === 'true' || $phone['selfie_camera_ois'] === '1') ? 'true' : 'false',                  // selfie_camera_ois
            ($phone['selfie_camera_flash'] === true || $phone['selfie_camera_flash'] === 'true' || $phone['selfie_camera_flash'] === '1') ? 'true' : 'false',                // selfie_camera_flash
            ($phone['popup_camera'] === true || $phone['popup_camera'] === 'true' || $phone['popup_camera'] === '1') ? 'true' : 'false',                       // popup_camera
            ($phone['under_display_camera'] === true || $phone['under_display_camera'] === 'true' || $phone['under_display_camera'] === '1') ? 'true' : 'false',               // under_display_camera

            // Audio
            ($phone['headphone_jack'] === true || $phone['headphone_jack'] === 'true' || $phone['headphone_jack'] === '1') ? 'true' : 'false',                     // headphone_jack
            ($phone['dual_speakers'] === true || $phone['dual_speakers'] === 'true' || $phone['dual_speakers'] === '1') ? 'true' : 'false',                      // dual_speakers

            // Communications
            $toPostgresArray($phone['wifi'] ?? []),              // wifi array
            $toPostgresArray($phone['bluetooth'] ?? []),         // bluetooth array
            ($phone['gps'] === true || $phone['gps'] === 'true' || $phone['gps'] === '1') ? 'true' : 'false',                               // gps
            ($phone['nfc'] === true || $phone['nfc'] === 'true' || $phone['nfc'] === '1') ? 'true' : 'false',                               // nfc
            ($phone['infrared'] === true || $phone['infrared'] === 'true' || $phone['infrared'] === '1') ? 'true' : 'false',                          // infrared
            ($phone['fm_radio'] === true || $phone['fm_radio'] === 'true' || $phone['fm_radio'] === '1') ? 'true' : 'false',                          // fm_radio
            $phone['usb'] ?? null,                              // usb

            // Sensors
            ($phone['accelerometer'] === true || $phone['accelerometer'] === 'true' || $phone['accelerometer'] === '1') ? 'true' : 'false',   // accelerometer
            ($phone['gyro'] === true || $phone['gyro'] === 'true' || $phone['gyro'] === '1') ? 'true' : 'false',           // gyro
            ($phone['compass'] === true || $phone['compass'] === 'true' || $phone['compass'] === '1') ? 'true' : 'false',        // compass
            ($phone['proximity'] === true || $phone['proximity'] === 'true' || $phone['proximity'] === '1') ? 'true' : 'false',      // proximity
            ($phone['barometer'] === true || $phone['barometer'] === 'true' || $phone['barometer'] === '1') ? 'true' : 'false',      // barometer
            ($phone['heart_rate'] === true || $phone['heart_rate'] === 'true' || $phone['heart_rate'] === '1') ? 'true' : 'false',     // heart_rate
            trim($phone['fingerprint'] ?? ''),                 // fingerprint

            // Battery
            $toNumber($phone['battery_capacity'] ?? '', 'int'), // battery_capacity
            ($phone['battery_sic'] === true || $phone['battery_sic'] === 'true' || $phone['battery_sic'] === '1') ? 'true' : 'false',    // battery_sic
            ($phone['battery_removable'] === true || $phone['battery_removable'] === 'true' || $phone['battery_removable'] === '1') ? 'true' : 'false', // battery_removable
            $toNumber($phone['wired_charging'] ?? '', 'int'), // wired_charging
            $toNumber($phone['wireless_charging'] ?? '', 'int'), // wireless_charging

            $id
        ];

        $result = $stmt->execute($values);

        if ($result) {
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log('Database error when updating phone: ' . json_encode($errorInfo));
            error_log('Update values: ' . json_encode($values));
            return false;
        }
    } catch (Exception $e) {
        error_log('Error updating phone: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a phone
 * 
 * @param int $id Phone ID
 * @return bool Success status
 */
function deletePhone($id)
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM phones WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log('Error deleting phone: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get phone by ID
 * 
 * @param int $id Phone ID
 * @return array|null Phone data or null if not found
 */
function getPhoneById($id)
{
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as chipset_name 
            FROM phones p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            LEFT JOIN chipsets c ON p.chipset_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $phone = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($phone) {
            $phone['brand'] = $phone['brand_name'];
            $phone['chipset'] = $phone['chipset_name'];

            // Decode PostgreSQL arrays back to PHP arrays
            $arrayFields = [
                'images',
                'network_2g',
                'network_3g',
                'network_4g',
                'network_5g',
                'sim_size',
                'ip_certificate',
                'wifi',
                'bluetooth',
                'main_camera_features',
                'selfie_camera_features'
            ];

            foreach ($arrayFields as $field) {
                if (isset($phone[$field]) && is_string($phone[$field])) {
                    // Handle PostgreSQL array format: {value1,value2,value3}
                    if (strpos($phone[$field], '{') === 0 && strpos($phone[$field], '}') === strlen($phone[$field]) - 1) {
                        $phone[$field] = array_filter(explode(',', trim($phone[$field], '{}')));
                        // Clean up any empty values
                        $phone[$field] = array_values(array_filter($phone[$field], function ($v) {
                            return !empty(trim($v));
                        }));
                    }
                }
            }

            // Map database field names to form field names for compatibility
            $phone['2g'] = $phone['network_2g'] ?? [];
            $phone['3g'] = $phone['network_3g'] ?? [];
            $phone['4g'] = $phone['network_4g'] ?? [];
            $phone['5g'] = $phone['network_5g'] ?? [];
        }

        return $phone ?: null;
    } catch (Exception $e) {
        error_log('Error getting phone by ID: ' . $e->getMessage());
        return null;
    }
}

/**
 * Generate a unique device ID from name and brand (legacy compatibility)
 * 
 * @param string $name Device name
 * @param string $brand Device brand
 * @return string Unique device ID
 */
function generateDeviceId($name, $brand)
{
    // This function is kept for backward compatibility
    // In database mode, IDs are auto-generated
    return uniqid($brand . '_' . $name . '_');
}
