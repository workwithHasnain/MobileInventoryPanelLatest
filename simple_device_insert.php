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

        // Check for exact duplicate device
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM phones WHERE LOWER(name) = LOWER(?) AND brand_id = ?");
        $stmt->execute([trim($phone['name']), $brand_id]);
        if ($stmt->fetchColumn() > 0) {
            return ['error' => 'A device with this name and brand already exists'];
        }

        // Helper function to convert array to PostgreSQL format
        $toPostgresArray = function ($arr) {
            if (empty($arr)) return null;
            if (!is_array($arr)) $arr = [$arr];
            return '{' . implode(',', array_map('trim', $arr)) . '}';
        };

        // Build the insert query including grouped spec columns as JSON (TEXT)
        $sql = "
            INSERT INTO phones (
                name, brand_id, brand, availability, price, year,
                release_date, image, images,
                weight, thickness, os, storage, card_slot,
                display_size, display_resolution, main_camera_resolution, main_camera_video,
                ram, chipset_name, battery_capacity, wired_charging, wireless_charging,
                network, launch, body, display, platform, memory,
                main_camera, selfie_camera, sound, comms, features, battery, misc
            ) VALUES (
                :name, :brand_id, :brand, :availability, :price, :year,
                :release_date, :image, :images,
                :weight, :thickness, :os, :storage, :card_slot,
                :display_size, :display_resolution, :main_camera_resolution, :main_camera_video,
                :ram, :chipset_name, :battery_capacity, :wired_charging, :wireless_charging,
                :network, :launch, :body, :display, :platform, :memory,
                :main_camera, :selfie_camera, :sound, :comms, :features, :battery, :misc
            )
        ";

        $stmt = $pdo->prepare($sql);

        // Utility to convert empty string to null
        $nullIfEmpty = function ($v) {
            if (!isset($v)) return null;
            if (is_string($v) && trim($v) === '') return null;
            return ($v === '') ? null : $v;
        };

        $params = [
            ':name' => trim($phone['name'] ?? ''),
            ':brand_id' => $brand_id,
            ':brand' => $nullIfEmpty($phone['brand'] ?? null),
            ':availability' => $nullIfEmpty($phone['availability'] ?? null),
            ':price' => $nullIfEmpty($phone['price'] ?? null),
            ':year' => $nullIfEmpty($phone['year'] ?? null),
            ':release_date' => $nullIfEmpty($phone['release_date'] ?? null),
            ':image' => $nullIfEmpty($phone['image'] ?? null),
            ':images' => $toPostgresArray($phone['images'] ?? []),

            // Highlight fields
            ':weight' => $nullIfEmpty($phone['weight'] ?? null),
            ':thickness' => $nullIfEmpty($phone['thickness'] ?? null),
            ':os' => $nullIfEmpty($phone['os'] ?? null),
            ':storage' => $nullIfEmpty($phone['storage'] ?? null),
            ':card_slot' => $phone['card_slot'] ?? false,

            // Stats fields
            ':display_size' => $nullIfEmpty($phone['display_size'] ?? null),
            ':display_resolution' => $nullIfEmpty($phone['display_resolution'] ?? null),
            ':main_camera_resolution' => $nullIfEmpty($phone['main_camera_resolution'] ?? null),
            ':main_camera_video' => $nullIfEmpty($phone['main_camera_video'] ?? null),
            ':ram' => $nullIfEmpty($phone['ram'] ?? null),
            ':chipset_name' => $nullIfEmpty($phone['chipset_name'] ?? null),
            ':battery_capacity' => $nullIfEmpty($phone['battery_capacity'] ?? null),
            ':wired_charging' => $nullIfEmpty($phone['wired_charging'] ?? null),
            ':wireless_charging' => $nullIfEmpty($phone['wireless_charging'] ?? null),

            // Grouped spec columns (JSON strings stored as TEXT)
            ':network' => $nullIfEmpty($phone['network'] ?? null),
            ':launch' => $nullIfEmpty($phone['launch'] ?? null),
            ':body' => $nullIfEmpty($phone['body'] ?? null),
            ':display' => $nullIfEmpty($phone['display'] ?? null),
            ':platform' => $nullIfEmpty($phone['platform'] ?? null),
            ':memory' => $nullIfEmpty($phone['memory'] ?? null),
            ':main_camera' => $nullIfEmpty($phone['main_camera'] ?? null),
            ':selfie_camera' => $nullIfEmpty($phone['selfie_camera'] ?? null),
            ':sound' => $nullIfEmpty($phone['sound'] ?? null),
            ':comms' => $nullIfEmpty($phone['comms'] ?? null),
            ':features' => $nullIfEmpty($phone['features'] ?? null),
            ':battery' => $nullIfEmpty($phone['battery'] ?? null),
            ':misc' => $nullIfEmpty($phone['misc'] ?? null),
        ];

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
