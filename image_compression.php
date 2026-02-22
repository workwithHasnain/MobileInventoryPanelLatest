<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Check if image compression is available (GD library)
 * 
 * @return bool True if GD library is loaded
 */
function isCompressionAvailable()
{
    return extension_loaded('gd');
}

/**
 * Compress image while maintaining dimensions using GD library
 * Reduces file size by adjusting quality and optimization
 * 
 * @param string $tempFilePath Path to the temporary uploaded file
 * @param int $jpegQuality JPEG quality (0-100, default 75 for good balance)
 * @return string Path to compressed image (or original if compression fails/not beneficial)
 */
function compressImageWithGD($tempFilePath, $jpegQuality = 75)
{
    if (!file_exists($tempFilePath)) {
        error_log('Compress error: File not found - ' . $tempFilePath);
        return null;
    }

    if (!isCompressionAvailable()) {
        error_log('Compress warning: GD library not available, returning original image');
        return $tempFilePath;
    }

    try {
        // Determine MIME type using file contents (not browser-reported type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempFilePath);
        finfo_close($finfo);

        $supportedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $supportedTypes)) {
            error_log('Compress warning: Unsupported MIME type - ' . $mimeType . ', returning original');
            return $tempFilePath;
        }

        // Load image from file
        if ($mimeType === 'image/jpeg') {
            $image = imagecreatefromjpeg($tempFilePath);
        } elseif ($mimeType === 'image/png') {
            $image = imagecreatefrompng($tempFilePath);
        } elseif ($mimeType === 'image/gif') {
            $image = imagecreatefromgif($tempFilePath);
        } else {
            return $tempFilePath;
        }

        if ($image === false) {
            error_log('Compress warning: Could not create image resource, returning original');
            return $tempFilePath;
        }

        // Create a temporary file for the compressed image
        $tempDir = sys_get_temp_dir();
        $compressedPath = tempnam($tempDir, 'img_compressed_');

        // Save compressed image based on format
        if ($mimeType === 'image/jpeg') {
            // JPEG: Use quality setting (lower = smaller, but lower quality)
            imagejpeg($image, $compressedPath, $jpegQuality);
        } elseif ($mimeType === 'image/png') {
            // PNG: Use maximum compression level (9 = max)
            // Note: PNG compression is lossless, so quality is preserved
            imagepng($image, $compressedPath, 9);
        } elseif ($mimeType === 'image/gif') {
            // GIF: Compression options are limited, just save optimized
            imagegif($image, $compressedPath);
        }

        imagedestroy($image);

        // Compare file sizes
        $originalSize = filesize($tempFilePath);
        $compressedSize = filesize($compressedPath);

        error_log('Image compression: ' . round($originalSize / 1024, 2) . 'KB -> ' . round($compressedSize / 1024, 2) . 'KB (' . round((1 - $compressedSize / $originalSize) * 100, 1) . '% reduction)');

        // Use compressed version if smaller, otherwise keep original
        if ($compressedSize < $originalSize) {
            return $compressedPath;
        } else {
            // Compression didn't help, remove temp compressed file
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }
            return $tempFilePath;
        }
    } catch (Exception $e) {
        error_log('Image compression exception: ' . $e->getMessage());
        return $tempFilePath; // Return original if compression fails
    }
}

/**
 * Main compression function with fallback
 * If GD is not available, attempts alternative compression methods
 * 
 * @param string $tempFilePath Path to the temporary uploaded file
 * @return string|null Path to compressed/original image or null on failure
 */
function compressImage($tempFilePath)
{
    if (!file_exists($tempFilePath)) {
        error_log('Compress error: File not found - ' . $tempFilePath);
        return null;
    }

    // Try GD library first (best option)
    if (isCompressionAvailable()) {
        return compressImageWithGD($tempFilePath);
    }

    // Fallback: Try using exec with external tools (if available)
    // This section attempts to use system tools if GD is not available
    try {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempFilePath);
        finfo_close($finfo);

        $originalSize = filesize($tempFilePath);
        $tempDir = sys_get_temp_dir();
        $compressedPath = tempnam($tempDir, 'img_compressed_');

        // Try ffmpeg/imagemagick if available
        if ($mimeType === 'image/jpeg' && shell_exec('which ffmpeg 2>/dev/null')) {
            // Attempt JPEG compression with ffmpeg
            $cmd = 'ffmpeg -i ' . escapeshellarg($tempFilePath) . ' -q:v 5 ' . escapeshellarg($compressedPath) . ' 2>&1';
            shell_exec($cmd);
            if (file_exists($compressedPath) && filesize($compressedPath) > 0) {
                $compressedSize = filesize($compressedPath);
                if ($compressedSize < $originalSize) {
                    error_log('Image compressed with ffmpeg: ' . round($originalSize / 1024, 2) . 'KB -> ' . round($compressedSize / 1024, 2) . 'KB');
                    return $compressedPath;
                }
            }
        }

        // Clean up if ffmpeg didn't produce results
        if (file_exists($compressedPath)) {
            unlink($compressedPath);
        }
    } catch (Exception $e) {
        error_log('Fallback compression attempt failed: ' . $e->getMessage());
    }

    // Final fallback: Return original file
    error_log('Image compression unavailable (no GD, no external tools), using original file: ' . basename($tempFilePath) . ' (' . round(filesize($tempFilePath) / 1024, 2) . 'KB)');
    return $tempFilePath;
}
