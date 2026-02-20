<?php

/**
 * bridge_import.php — Server-side proxy for the GSMArena extension
 * 
 * Sits on the same server as import_device.php. Receives multipart data 
 * from the browser extension, forwards it to import_device.php via local cURL.
 * Always returns clean JSON — never HTML, never CORS issues.
 * 
 * Flow: Extension → bridge_import.php → import_device.php (same server)
 * 
 * Staff just need Chrome + the extension installed. No XAMPP needed locally.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

// ====================================================================
// Get API key from header
// ====================================================================
$allHeaders = function_exists('getallheaders') ? getallheaders() : [];
$apiKey = $_SERVER['HTTP_X_API_KEY'] 
    ?? $allHeaders['X-API-Key'] 
    ?? $allHeaders['X-Api-Key'] 
    ?? $allHeaders['x-api-key'] 
    ?? '';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'error' => 'Missing X-API-Key header']);
    exit;
}

// ====================================================================
// Validate we received data
// ====================================================================
$deviceData = $_POST['device_data'] ?? '';
if (empty($deviceData)) {
    echo json_encode(['success' => false, 'error' => 'No device_data received in POST body']);
    exit;
}

// ====================================================================
// Build the local URL to import_device.php (same server)
// ====================================================================
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g. /gsmarena-extension
$parentDir = dirname($scriptDir);               // e.g. / (root)
$importUrl = $scheme . '://' . $host . rtrim($parentDir, '/') . '/import_device.php';

// ====================================================================
// Build cURL multipart request
// ====================================================================
$postFields = [
    'device_data' => $deviceData
];

// Attach image files
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if (!empty($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['images']['tmp_name'][$i];
            $fileName = $_FILES['images']['name'][$i];
            $mimeType = $_FILES['images']['type'][$i];

            $postFields["images[$i]"] = new CURLFile($tmpPath, $mimeType, $fileName);
        }
    }
}

// ====================================================================
// Send to import_device.php via cURL (local call, no redirects)
// ====================================================================
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL            => $importUrl,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: ' . $apiKey,
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Same server, skip SSL verification
    CURLOPT_FOLLOWLOCATION => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

// ====================================================================
// Handle response
// ====================================================================

if ($curlErrno !== 0) {
    echo json_encode([
        'success' => false,
        'error' => "Internal request failed: {$curlError}",
        'target_url' => $importUrl
    ]);
    exit;
}

if ($httpCode >= 300 && $httpCode < 400) {
    echo json_encode([
        'success' => false,
        'error' => "import_device.php returned a redirect (HTTP {$httpCode}). Check server config.",
        'target_url' => $importUrl
    ]);
    exit;
}

// Try to parse response as JSON
$decoded = json_decode($response, true);

if ($decoded !== null) {
    echo json_encode($decoded);
} else {
    $snippet = strip_tags($response);
    $snippet = preg_replace('/\s+/', ' ', $snippet);
    $snippet = trim(substr($snippet, 0, 500));

    echo json_encode([
        'success' => false,
        'error' => "import_device.php returned HTTP {$httpCode} with non-JSON response",
        'details' => $snippet,
        'target_url' => $importUrl
    ]);
}
