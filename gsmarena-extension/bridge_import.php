<?php

/**
 * bridge_import.php — Local proxy for the GSMArena extension
 * 
 * Runs on localhost (XAMPP). Receives multipart data from the browser extension,
 * then forwards it to the remote server (devicesarena.com) using cURL.
 * Returns clean JSON responses — no HTML, no CORS issues.
 * 
 * Flow: Extension → localhost/bridge_import.php → devicesarena.com/import_device.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Remote-URL');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

// ====================================================================
// Get the remote server URL from the header
// ====================================================================
$remoteUrl = $_SERVER['HTTP_X_REMOTE_URL'] ?? '';
if (empty($remoteUrl)) {
    echo json_encode(['success' => false, 'error' => 'Missing X-Remote-URL header. Set your remote server URL.']);
    exit;
}

// Ensure it ends with /import_device.php
$remoteUrl = rtrim($remoteUrl, '/');
if (!str_ends_with($remoteUrl, '/import_device.php')) {
    $remoteUrl .= '/import_device.php';
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
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
// Build cURL multipart request to remote server
// ====================================================================
$postFields = [
    'device_data' => $deviceData
];

// Attach image files (validate each does not exceed 500KB)
$maxImageSize = 500 * 1024; // 500KB
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if (!empty($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['images']['tmp_name'][$i];
            $fileName = $_FILES['images']['name'][$i];
            $mimeType = $_FILES['images']['type'][$i];
            $fileSize = $_FILES['images']['size'][$i];

            // Validate image size
            if ($fileSize > $maxImageSize) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Image ' . ($i + 1) . ' ("' . $fileName . '") exceeds 500KB limit (' . round($fileSize / 1024) . 'KB). Please compress it before importing.'
                ]);
                exit;
            }

            $postFields["images[$i]"] = new CURLFile($tmpPath, $mimeType, $fileName);
        }
    }
}

// ====================================================================
// Send to remote server via cURL
// ====================================================================
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL            => $remoteUrl,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER     => [
        'X-API-Key: ' . $apiKey,
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects — they strip custom headers
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

// ====================================================================
// Handle response
// ====================================================================

// cURL failed entirely
if ($curlErrno !== 0) {
    echo json_encode([
        'success' => false,
        'error' => "Connection to remote server failed: {$curlError} (code {$curlErrno})",
        'remote_url' => $remoteUrl
    ]);
    exit;
}

// Handle redirects (301/302) — the server is redirecting, give user the correct URL
if ($httpCode >= 300 && $httpCode < 400) {
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    echo json_encode([
        'success' => false,
        'error' => "Server returned a redirect (HTTP {$httpCode}). Update your Remote Server URL to: " . rtrim($redirectUrl, '/import_device.php'),
        'redirect_url' => $redirectUrl
    ]);
    exit;
}

// Try to parse remote response as JSON
$decoded = json_decode($response, true);

if ($decoded !== null) {
    // Remote returned valid JSON — pass it through
    echo json_encode($decoded);
} else {
    // Remote returned non-JSON (HTML error page, etc.)
    // Extract useful info and return as clean JSON error
    $snippet = strip_tags($response);
    $snippet = preg_replace('/\s+/', ' ', $snippet);
    $snippet = trim(substr($snippet, 0, 500));

    echo json_encode([
        'success' => false,
        'error' => "Remote server returned HTTP {$httpCode} with non-JSON response",
        'details' => $snippet,
        'remote_url' => $remoteUrl
    ]);
}
