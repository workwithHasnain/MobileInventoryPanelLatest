<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'database_functions.php';

/**
 * Simple function to insert a new device into the database
 * 
 * @param array $phone Phone data from the form submission
 * @return array|bool Returns true on success, array with error on failure
 */
function simpleAddDevice($phone)
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

        // Build the insert query with prepared statements
        $sql = "
            INSERT INTO phones (
                name, brand_id, brand, chipset_id, availability, price, year,
                release_date, image, images, 
                network_2g, network_3g, network_4g, network_5g,
                dual_sim, esim, sim_size,
                dimensions, form_factor, keyboard, height, width, thickness, weight,
                ip_certificate, color, back_material, frame_material,
                display_type, display_size, display_resolution, display_density,
                display_technology, display_notch, refresh_rate, hdr, billion_colors,
                os, os_version, chipset, cpu_cores,
                ram, storage, card_slot,
                main_camera_count, main_camera_resolution, main_camera_features,
                main_camera_video, main_camera_ois, main_camera_telephoto,
                main_camera_ultrawide, main_camera_flash, main_camera_f_number,
                selfie_camera_count, selfie_camera_resolution, selfie_camera_features,
                selfie_camera_video, selfie_camera_ois, selfie_camera_flash,
                popup_camera, under_display_camera,
                headphone_jack, dual_speakers,
                wifi, bluetooth, gps, nfc, infrared, fm_radio, usb,
                accelerometer, gyro, compass, proximity, barometer,
                heart_rate, fingerprint,
                battery_capacity, battery_sic, battery_removable,
                wired_charging, wireless_charging,
                colors
            ) VALUES (
                :name, :brand_id, :brand, :chipset_id, :availability, :price, :year,
                :release_date, :image, :images, 
                :network_2g, :network_3g, :network_4g, :network_5g,
                :dual_sim, :esim, :sim_size,
                :dimensions, :form_factor, :keyboard, :height, :width, :thickness, :weight,
                :ip_certificate, :color, :back_material, :frame_material,
                :display_type, :display_size, :display_resolution, :display_density,
                :display_technology, :display_notch, :refresh_rate, :hdr, :billion_colors,
                :os, :os_version, :chipset, :cpu_cores,
                :ram, :storage, :card_slot,
                :main_camera_count, :main_camera_resolution, :main_camera_features,
                :main_camera_video, :main_camera_ois, :main_camera_telephoto,
                :main_camera_ultrawide, :main_camera_flash, :main_camera_f_number,
                :selfie_camera_count, :selfie_camera_resolution, :selfie_camera_features,
                :selfie_camera_video, :selfie_camera_ois, :selfie_camera_flash,
                :popup_camera, :under_display_camera,
                :headphone_jack, :dual_speakers,
                :wifi, :bluetooth, :gps, :nfc, :infrared, :fm_radio, :usb,
                :accelerometer, :gyro, :compass, :proximity, :barometer,
                :heart_rate, :fingerprint,
                :battery_capacity, :battery_sic, :battery_removable,
                :wired_charging, :wireless_charging,
                :colors
            )";

        $stmt = $pdo->prepare($sql);

        // Bind parameters with proper type conversion
        $params = [
            // Basic Info
            ':name' => trim($phone['name'] ?? ''),
            ':brand_id' => $brand_id,
            ':brand' => trim($phone['brand'] ?? ''),
            ':chipset_id' => $chipset_id,
            ':availability' => trim($phone['availability'] ?? ''),
            ':price' => $toNumber($phone['price'] ?? ''),
            ':year' => $toNumber($phone['year'] ?? '', 'int'),
            ':release_date' => $phone['release_date'] ?? null,
            ':image' => $phone['image'] ?? null,
            ':images' => $toPostgresArray($phone['images'] ?? []),

            // Network
            ':network_2g' => $toPostgresArray($phone['2g'] ?? []),
            ':network_3g' => $toPostgresArray($phone['3g'] ?? []),
            ':network_4g' => $toPostgresArray($phone['4g'] ?? []),
            ':network_5g' => $toPostgresArray($phone['5g'] ?? []),
            ':dual_sim' => ($phone['dual_sim'] ?? false) ? 'true' : 'false',
            ':esim' => ($phone['esim'] ?? false) ? 'true' : 'false',
            ':sim_size' => $toPostgresArray($phone['sim_size'] ?? []),

            // Body
            ':dimensions' => trim($phone['dimensions'] ?? ''),
            ':form_factor' => trim($phone['form_factor'] ?? ''),
            ':keyboard' => trim($phone['keyboard'] ?? ''),
            ':height' => $toNumber($phone['height'] ?? ''),
            ':width' => $toNumber($phone['width'] ?? ''),
            ':thickness' => $toNumber($phone['thickness'] ?? ''),
            ':weight' => $toNumber($phone['weight'] ?? ''),
            ':ip_certificate' => $toPostgresArray($phone['ip_certificate'] ?? []),
            ':color' => trim($phone['color'] ?? ''),
            ':back_material' => trim($phone['back_material'] ?? ''),
            ':frame_material' => trim($phone['frame_material'] ?? ''),

            // Display
            ':display_type' => trim($phone['display_type'] ?? ''),
            ':display_size' => $toNumber($phone['display_size'] ?? ''),
            ':display_resolution' => trim($phone['display_resolution'] ?? ''),
            ':display_density' => $toNumber($phone['display_density'] ?? '', 'int'),
            ':display_technology' => trim($phone['display_technology'] ?? ''),
            ':display_notch' => trim($phone['display_notch'] ?? ''),
            ':refresh_rate' => trim($phone['refresh_rate'] ?? ''),
            ':hdr' => ($phone['hdr'] ?? false) ? 'true' : 'false',
            ':billion_colors' => ($phone['billion_colors'] ?? false) ? 'true' : 'false',

            // Platform
            ':os' => trim($phone['os'] ?? ''),
            ':os_version' => trim($phone['os_version'] ?? ''),
            ':chipset' => trim($phone['chipset'] ?? ''),
            ':cpu_cores' => trim($phone['cpu_cores'] ?? ''),

            // Memory
            ':ram' => $toNumber($phone['ram'] ?? ''),
            ':storage' => $toNumber($phone['storage'] ?? '', 'int'),
            ':card_slot' => trim($phone['card_slot'] ?? ''),

            // Main Camera
            ':main_camera_count' => $toNumber($phone['main_camera_count'] ?? '', 'int'),
            ':main_camera_resolution' => $toNumber($phone['main_camera_resolution'] ?? '', 'float'),
            ':main_camera_features' => $toPostgresArray($phone['main_camera_features'] ?? []),
            ':main_camera_video' => trim($phone['main_camera_video'] ?? ''),
            ':main_camera_ois' => ($phone['main_camera_ois'] ?? false) ? 'true' : 'false',
            ':main_camera_telephoto' => ($phone['main_camera_telephoto'] ?? false) ? 'true' : 'false',
            ':main_camera_ultrawide' => ($phone['main_camera_ultrawide'] ?? false) ? 'true' : 'false',
            ':main_camera_flash' => trim($phone['main_camera_flash'] ?? ''),
            ':main_camera_f_number' => $toNumber($phone['main_camera_f_number'] ?? '', 'float'),

            // Selfie Camera
            ':selfie_camera_count' => $toNumber($phone['selfie_camera_count'] ?? '', 'int'),
            ':selfie_camera_resolution' => $toNumber($phone['selfie_camera_resolution'] ?? '', 'float'),
            ':selfie_camera_features' => $toPostgresArray($phone['selfie_camera_features'] ?? []),
            ':selfie_camera_video' => trim($phone['selfie_camera_video'] ?? ''),
            ':selfie_camera_ois' => ($phone['selfie_camera_ois'] ?? false) ? 'true' : 'false',
            ':selfie_camera_flash' => ($phone['selfie_camera_flash'] ?? false) ? 'true' : 'false',
            ':popup_camera' => ($phone['popup_camera'] ?? false) ? 'true' : 'false',
            ':under_display_camera' => ($phone['under_display_camera'] ?? false) ? 'true' : 'false',

            // Audio
            ':headphone_jack' => ($phone['headphone_jack'] ?? false) ? 'true' : 'false',
            ':dual_speakers' => ($phone['dual_speakers'] ?? false) ? 'true' : 'false',

            // Communications
            ':wifi' => $toPostgresArray($phone['wifi'] ?? []),
            ':bluetooth' => $toPostgresArray($phone['bluetooth'] ?? []),
            ':gps' => ($phone['gps'] ?? false) ? 'true' : 'false',
            ':nfc' => ($phone['nfc'] ?? false) ? 'true' : 'false',
            ':infrared' => ($phone['infrared'] ?? false) ? 'true' : 'false',
            ':fm_radio' => ($phone['fm_radio'] ?? false) ? 'true' : 'false',
            ':usb' => trim($phone['usb'] ?? ''),

            // Sensors
            ':accelerometer' => ($phone['accelerometer'] ?? false) ? 'true' : 'false',
            ':gyro' => ($phone['gyro'] ?? false) ? 'true' : 'false',
            ':compass' => ($phone['compass'] ?? false) ? 'true' : 'false',
            ':proximity' => ($phone['proximity'] ?? false) ? 'true' : 'false',
            ':barometer' => ($phone['barometer'] ?? false) ? 'true' : 'false',
            ':heart_rate' => ($phone['heart_rate'] ?? false) ? 'true' : 'false',
            ':fingerprint' => trim($phone['fingerprint'] ?? ''),

            // Battery
            ':battery_capacity' => $toNumber($phone['battery_capacity'] ?? '', 'int'),
            ':battery_sic' => ($phone['battery_sic'] ?? false) ? 'true' : 'false',
            ':battery_removable' => ($phone['battery_removable'] ?? false) ? 'true' : 'false',
            ':wired_charging' => $toNumber($phone['wired_charging'] ?? '', 'int'),
            ':wireless_charging' => $toNumber($phone['wireless_charging'] ?? '', 'int'),

            // Additional
            ':colors' => $toPostgresArray($phone['colors'] ?? [])
        ];

        // Execute the query with the parameters
        $result = $stmt->execute($params);

        if ($result) {
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log('Database error when adding device: ' . json_encode($errorInfo));
            return ['error' => 'Database error: ' . $errorInfo[2]];
        }
    } catch (Exception $e) {
        error_log('Error adding device: ' . $e->getMessage());
        return ['error' => 'System error: ' . $e->getMessage()];
    }
}
