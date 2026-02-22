<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'database_functions.php';
require_once 'sitemap_management.php';

/**
 * Simple function to update an existing device in the database
 * 
 * @param array $phone Phone data from the form submission
 * @return array|bool Returns true on success, array with error on failure
 */
function simpleUpdateDevice($id, $phone)
{
    try {
        $pdo = getConnection();

        // Validate ID and fetch existing device
        $id = (int)$id;
        if ($id <= 0) {
            return ['error' => 'Invalid device ID'];
        }
        $stmt = $pdo->prepare("SELECT * FROM phones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            return ['error' => 'Device not found'];
        }

        // Validate required fields (name & brand can be updated; fallback to existing if missing)
        $incomingName = isset($phone['name']) ? trim((string)$phone['name']) : '';
        $name = $incomingName !== '' ? $incomingName : ($existing['name'] ?? '');
        if ($name === '') {
            return ['error' => 'Phone name is required'];
        }

        // Resolve brand and brand_id (create brand if not exists). If empty, keep existing.
        $incomingBrand = isset($phone['brand']) ? trim((string)$phone['brand']) : '';
        $brandToUse = $incomingBrand !== '' ? $incomingBrand : ($existing['brand'] ?? '');
        if ($brandToUse === '') {
            return ['error' => 'Brand is required'];
        }

        $stmt = $pdo->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$brandToUse]);
        $brand_id = $stmt->fetchColumn();
        if (!$brand_id) {
            $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?) RETURNING id");
            $stmt->execute([$brandToUse]);
            $brand_id = $stmt->fetchColumn();
        }

        // Helper function to convert array to PostgreSQL format
        $toPostgresArray = function ($arr) {
            if (empty($arr)) return null;
            if (!is_array($arr)) $arr = [$arr];
            return '{' . implode(',', array_map('trim', $arr)) . '}';
        };

        // Build the update query (update all editable columns, set updated_at)
        $sql = "
            UPDATE phones SET
                release_date = :release_date,
                name = :name,
                brand_id = :brand_id,
                brand = :brand,
                year = :year,
                availability = :availability,
                price = :price,
                device_page_color = :device_page_color,
                image = :image,
                images = :images,
                network = :network,
                launch = :launch,
                body = :body,
                display = :display,
                hardware = :hardware,
                memory = :memory,
                main_camera = :main_camera,
                selfie_camera = :selfie_camera,
                multimedia = :multimedia,
                connectivity = :connectivity,
                features = :features,
                battery = :battery,
                general_info = :general_info,
                weight = :weight,
                thickness = :thickness,
                os = :os,
                storage = :storage,
                card_slot = :card_slot,
                display_size = :display_size,
                display_resolution = :display_resolution,
                main_camera_resolution = :main_camera_resolution,
                main_camera_video = :main_camera_video,
                ram = :ram,
                chipset_name = :chipset_name,
                battery_capacity = :battery_capacity,
                wired_charging = :wired_charging,
                wireless_charging = :wireless_charging,
                slug = :slug,
                meta_title = :meta_title,
                meta_desc = :meta_desc,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        // Utility to convert empty string to null
        $nullIfEmpty = function ($v) {
            if (!isset($v)) return null;
            if (is_string($v) && trim($v) === '') return null;
            return ($v === '') ? null : $v;
        };

        // Decide image/images: if not provided, keep existing
        $imageVal = array_key_exists('image', $phone) ? $phone['image'] : ($existing['image'] ?? null);
        $imagesVal = array_key_exists('images', $phone) ? $phone['images'] : ($existing['images'] ?? null);

        $params = [
            ':id' => $id,
            ':release_date' => $nullIfEmpty($phone['release_date'] ?? $existing['release_date'] ?? null),
            ':name' => $name,
            ':brand_id' => $brand_id,
            ':brand' => $brandToUse,
            ':year' => $nullIfEmpty($phone['year'] ?? $existing['year'] ?? null),
            ':availability' => $nullIfEmpty($phone['availability'] ?? $existing['availability'] ?? null),
            ':price' => $nullIfEmpty($phone['price'] ?? $existing['price'] ?? null),
            ':device_page_color' => $nullIfEmpty($phone['device_page_color'] ?? $existing['device_page_color'] ?? null),
            ':image' => $nullIfEmpty($imageVal),
            ':images' => $toPostgresArray($imagesVal ?? []),

            // Grouped spec columns (JSON strings stored as TEXT)
            ':network' => $nullIfEmpty($phone['network'] ?? $existing['network'] ?? null),
            ':launch' => $nullIfEmpty($phone['launch'] ?? $existing['launch'] ?? null),
            ':body' => $nullIfEmpty($phone['body'] ?? $existing['body'] ?? null),
            ':display' => $nullIfEmpty($phone['display'] ?? $existing['display'] ?? null),
            ':hardware' => $nullIfEmpty($phone['hardware'] ?? $existing['hardware'] ?? null),
            ':memory' => $nullIfEmpty($phone['memory'] ?? $existing['memory'] ?? null),
            ':main_camera' => $nullIfEmpty($phone['main_camera'] ?? $existing['main_camera'] ?? null),
            ':selfie_camera' => $nullIfEmpty($phone['selfie_camera'] ?? $existing['selfie_camera'] ?? null),
            ':multimedia' => $nullIfEmpty($phone['multimedia'] ?? $existing['multimedia'] ?? null),
            ':connectivity' => $nullIfEmpty($phone['connectivity'] ?? $existing['connectivity'] ?? null),
            ':features' => $nullIfEmpty($phone['features'] ?? $existing['features'] ?? null),
            ':battery' => $nullIfEmpty($phone['battery'] ?? $existing['battery'] ?? null),
            ':general_info' => $nullIfEmpty($phone['general_info'] ?? $existing['general_info'] ?? null),

            // Highlight fields
            ':weight' => $nullIfEmpty($phone['weight'] ?? $existing['weight'] ?? null),
            ':thickness' => $nullIfEmpty($phone['thickness'] ?? $existing['thickness'] ?? null),
            ':os' => $nullIfEmpty($phone['os'] ?? $existing['os'] ?? null),
            ':storage' => $nullIfEmpty($phone['storage'] ?? $existing['storage'] ?? null),
            ':card_slot' => $nullIfEmpty($phone['card_slot'] ?? $existing['card_slot'] ?? null),

            // Stats fields
            ':display_size' => $nullIfEmpty($phone['display_size'] ?? $existing['display_size'] ?? null),
            ':display_resolution' => $nullIfEmpty($phone['display_resolution'] ?? $existing['display_resolution'] ?? null),
            ':main_camera_resolution' => $nullIfEmpty($phone['main_camera_resolution'] ?? $existing['main_camera_resolution'] ?? null),
            ':main_camera_video' => $nullIfEmpty($phone['main_camera_video'] ?? $existing['main_camera_video'] ?? null),
            ':ram' => $nullIfEmpty($phone['ram'] ?? $existing['ram'] ?? null),
            ':chipset_name' => $nullIfEmpty($phone['chipset_name'] ?? $existing['chipset_name'] ?? null),
            ':battery_capacity' => $nullIfEmpty($phone['battery_capacity'] ?? $existing['battery_capacity'] ?? null),
            ':wired_charging' => $nullIfEmpty($phone['wired_charging'] ?? $existing['wired_charging'] ?? null),
            ':wireless_charging' => $nullIfEmpty($phone['wireless_charging'] ?? $existing['wireless_charging'] ?? null),

            // SEO fields
            ':slug' => $nullIfEmpty($phone['slug'] ?? $existing['slug'] ?? null),
            ':meta_title' => $nullIfEmpty($phone['meta_title'] ?? $existing['meta_title'] ?? null),
            ':meta_desc' => $nullIfEmpty($phone['meta_desc'] ?? $existing['meta_desc'] ?? null),
        ];

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            // Handle sitemap updates if device needs sitemap management
            $old_slug = $existing['slug'] ?? '';
            $new_slug = $params[':slug'] ?? '';
            if (!empty($new_slug)) {
                if (!empty($old_slug) && $old_slug !== $new_slug) {
                    // Slug changed - update the URL in sitemap
                    updateDeviceInSitemap($old_slug, $new_slug, date('Y-m-d'));
                } else {
                    // No slug change - just update lastmod date
                    updateDeviceLastmodInSitemap($new_slug, date('Y-m-d'));
                }
            }
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log('Database error when updating device: ' . json_encode($errorInfo));
            error_log('All params: ' . json_encode($params));
            return ['error' => 'Database error: ' . $errorInfo[2]];
        }
    } catch (Exception $e) {
        error_log('Error updating device: ' . $e->getMessage());
        return ['error' => 'System error: ' . $e->getMessage()];
    }
}
