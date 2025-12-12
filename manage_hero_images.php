<?php
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

// Define pages that have hero images
$heroPages = [
    'reviews' => 'Reviews Page',
    'featured' => 'Featured Page',
    'phonefinder' => 'Phone Finder Page',
    'compare' => 'Compare Page'
];

$heroImagesDir = __DIR__ . '/hero-images/';

// Ensure hero-images directory exists
if (!is_dir($heroImagesDir)) {
    mkdir($heroImagesDir, 0755, true);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'list') {
        // List all hero images with their status
        $pages = [];
        foreach ($heroPages as $pageName => $displayName) {
            $filename = $pageName . '-hero.png';
            $filepath = $heroImagesDir . $filename;
            $webPath = 'hero-images/' . $filename;

            $pages[] = [
                'name' => $pageName,
                'display_name' => $displayName,
                'filename' => $filename,
                'path' => $webPath,
                'exists' => file_exists($filepath)
            ];
        }

        echo json_encode([
            'success' => true,
            'pages' => $pages
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $pageName = $_POST['page_name'] ?? '';

        // Validate page name
        if (!isset($heroPages[$pageName])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid page name.'
            ]);
            exit;
        }

        // Check if file was uploaded
        if (!isset($_FILES['hero_image']) || $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => 'No file uploaded or upload error occurred.'
            ]);
            exit;
        }

        $file = $_FILES['hero_image'];

        // Validate file type (must be PNG)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'image/png') {
            echo json_encode([
                'success' => false,
                'message' => 'Only PNG images are allowed.'
            ]);
            exit;
        }

        // Validate file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'png') {
            echo json_encode([
                'success' => false,
                'message' => 'File must have .png extension.'
            ]);
            exit;
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'File size must be less than 5MB.'
            ]);
            exit;
        }

        // Validate image dimensions
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid image file.'
            ]);
            exit;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width !== 712 || $height !== 340) {
            echo json_encode([
                'success' => false,
                'message' => "Image dimensions must be exactly 712 x 340 pixels. Uploaded image is {$width} x {$height} pixels."
            ]);
            exit;
        }

        // Generate filename
        $newFilename = $pageName . '-hero.png';
        $destination = $heroImagesDir . $newFilename;

        // Delete old file if it exists
        if (file_exists($destination)) {
            unlink($destination);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode([
                'success' => true,
                'message' => 'Hero image uploaded successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save image file.'
            ]);
        }
        exit;
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid request.'
]);
