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
        // Utility to convert empty string to null
        $nullIfEmpty = function ($v) {
            if (!isset($v)) return null;
            if (is_string($v) && trim($v) === '') return null;
            return ($v === '') ? null : $v;
        };

        $params = [
            // Basic Info
            ':name' => trim($phone['name'] ?? ''),
            ':brand_id' => $brand_id,
            ':brand' => $nullIfEmpty($phone['brand'] ?? null),
            ':chipset_id' => $chipset_id,
            ':availability' => $nullIfEmpty($phone['availability'] ?? null),
            ':price' => $nullIfEmpty($phone['price'] ?? null),
            ':year' => $nullIfEmpty($phone['year'] ?? null),
            ':release_date' => $nullIfEmpty($phone['release_date'] ?? null),
            ':image' => $nullIfEmpty($phone['image'] ?? null),
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
            ':dimensions' => $nullIfEmpty($phone['dimensions'] ?? null),
            ':form_factor' => $nullIfEmpty($phone['form_factor'] ?? null),
            ':keyboard' => $nullIfEmpty($phone['keyboard'] ?? null),
            ':height' => $nullIfEmpty($phone['height'] ?? null),
            ':width' => $nullIfEmpty($phone['width'] ?? null),
            ':thickness' => $nullIfEmpty($phone['thickness'] ?? null),
            ':weight' => $nullIfEmpty($phone['weight'] ?? null),
            ':ip_certificate' => $toPostgresArray($phone['ip_certificate'] ?? []),
            ':color' => $nullIfEmpty($phone['color'] ?? null),
            ':back_material' => $nullIfEmpty($phone['back_material'] ?? null),
            ':frame_material' => $nullIfEmpty($phone['frame_material'] ?? null),

            // Display
            ':display_type' => $nullIfEmpty($phone['display_type'] ?? null),
            ':display_size' => $nullIfEmpty($phone['display_size'] ?? null),
            ':display_resolution' => $nullIfEmpty($phone['display_resolution'] ?? null),
            ':display_density' => $nullIfEmpty($phone['display_density'] ?? null),
            ':display_technology' => $nullIfEmpty($phone['display_technology'] ?? null),
            ':display_notch' => $nullIfEmpty($phone['display_notch'] ?? null),
            ':refresh_rate' => $nullIfEmpty($phone['refresh_rate'] ?? null),
            ':hdr' => ($phone['hdr'] ?? false) ? 'true' : 'false',
            ':billion_colors' => ($phone['billion_colors'] ?? false) ? 'true' : 'false',

            // Platform
            ':os' => $nullIfEmpty($phone['os'] ?? null),
            ':os_version' => $nullIfEmpty($phone['os_version'] ?? null),
            ':chipset' => $nullIfEmpty($phone['chipset'] ?? null),
            ':cpu_cores' => $nullIfEmpty($phone['cpu_cores'] ?? null),

            // Memory
            ':ram' => trim($phone['ram'] ?? ''),
            ':storage' => trim($phone['storage'] ?? ''),
            ':card_slot' => $nullIfEmpty($phone['card_slot'] ?? null),

            // Main Camera
            ':main_camera_count' => $toNumber($phone['main_camera_count'] ?? '', 'int'),
            ':main_camera_resolution' => $toNumber($phone['main_camera_resolution'] ?? '', 'float'),
            ':main_camera_features' => $toPostgresArray($phone['main_camera_features'] ?? []),
            ':main_camera_video' => $nullIfEmpty($phone['main_camera_video'] ?? null),
            ':main_camera_ois' => ($phone['main_camera_ois'] ?? false) ? 'true' : 'false',
            ':main_camera_telephoto' => ($phone['main_camera_telephoto'] ?? false) ? 'true' : 'false',
            ':main_camera_ultrawide' => ($phone['main_camera_ultrawide'] ?? false) ? 'true' : 'false',
            ':main_camera_flash' => ($phone['main_camera_flash'] ?? false) ? 'true' : 'false',
            ':main_camera_f_number' => $nullIfEmpty($phone['main_camera_f_number'] ?? null),

            // Selfie Camera
            ':selfie_camera_count' => $toNumber($phone['selfie_camera_count'] ?? '', 'int'),
            ':selfie_camera_resolution' => $toNumber($phone['selfie_camera_resolution'] ?? '', 'float'),
            ':selfie_camera_features' => $toPostgresArray($phone['selfie_camera_features'] ?? []),
            ':selfie_camera_video' => $nullIfEmpty($phone['selfie_camera_video'] ?? null),
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
            ':usb' => $nullIfEmpty($phone['usb'] ?? null),

            // Sensors
            ':accelerometer' => ($phone['accelerometer'] ?? false) ? 'true' : 'false',
            ':gyro' => ($phone['gyro'] ?? false) ? 'true' : 'false',
            ':compass' => ($phone['compass'] ?? false) ? 'true' : 'false',
            ':proximity' => ($phone['proximity'] ?? false) ? 'true' : 'false',
            ':barometer' => ($phone['barometer'] ?? false) ? 'true' : 'false',
            ':heart_rate' => ($phone['heart_rate'] ?? false) ? 'true' : 'false',
            ':fingerprint' => trim($phone['fingerprint'] ?? ''),

            // Battery
            ':battery_capacity' => $nullIfEmpty($phone['battery_capacity'] ?? null),
            ':battery_sic' => ($phone['battery_sic'] ?? false) ? 'true' : 'false',
            ':battery_removable' => ($phone['battery_removable'] ?? false) ? 'true' : 'false',
            ':wired_charging' => $nullIfEmpty($phone['wired_charging'] ?? null),
            ':wireless_charging' => $nullIfEmpty($phone['wireless_charging'] ?? null),

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
