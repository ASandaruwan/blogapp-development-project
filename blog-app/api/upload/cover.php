<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate CSRF Protection
validateCsrfRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['cover'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No image file uploaded']);
    exit;
}

$file = $_FILES['cover'];

// Validate file error
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Upload error code: ' . $file['error']]);
    exit;
}

// Max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Image size must be less than 5MB']);
    exit;
}

// Allowed MIME types
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!array_key_exists($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF']);
    exit;
}

$extension = $allowedTypes[$mimeType];
$fileName = 'cover_' . uniqid('', true) . '.' . $extension;
$targetDir = __DIR__ . '/../../uploads/covers';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetPath = $targetDir . '/' . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $publicUrl = '/uploads/covers/' . $fileName;
    echo json_encode([
        'success' => true,
        'url' => $publicUrl,
        'full_url' => BASE_URL . $publicUrl
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
}
