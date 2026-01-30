<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/auth_check.php';

requireAuth();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['file'];
    $type = $_POST['type'] ?? 'general';

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed');
    }

    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum 10MB allowed');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Create specific directory for damage photos
    $damagePhotosDir = $uploadDir . 'damage_photos/';
    if (!is_dir($damagePhotosDir)) {
        mkdir($damagePhotosDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('damage_') . '_' . time() . '.' . $extension;
    $targetPath = $damagePhotosDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Create thumbnail if it's an image
        $thumbnailPath = $damagePhotosDir . 'thumb_' . $filename;
        createThumbnail($targetPath, $thumbnailPath, 150, 150);

        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'url' => 'uploads/damage_photos/' . $filename,
            'thumbnail_url' => 'uploads/damage_photos/thumb_' . $filename
        ]);
    } else {
        throw new Exception('Failed to move uploaded file');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createThumbnail($sourcePath, $thumbnailPath, $thumbWidth, $thumbHeight) {
    try {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Create source image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Calculate thumbnail dimensions maintaining aspect ratio
        $aspectRatio = $sourceWidth / $sourceHeight;
        if ($aspectRatio > 1) {
            $newWidth = $thumbWidth;
            $newHeight = $thumbWidth / $aspectRatio;
        } else {
            $newHeight = $thumbHeight;
            $newWidth = $thumbHeight * $aspectRatio;
        }

        // Create thumbnail image
        $thumbnailImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
            imagealphablending($thumbnailImage, false);
            imagesavealpha($thumbnailImage, true);
            $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
            imagefilledrectangle($thumbnailImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Copy and resize
        imagecopyresampled(
            $thumbnailImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Save thumbnail
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbnailImage, $thumbnailPath, 90);
                break;
            case 'image/png':
                imagepng($thumbnailImage, $thumbnailPath);
                break;
            case 'image/gif':
                imagegif($thumbnailImage, $thumbnailPath);
                break;
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($thumbnailImage);

        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>