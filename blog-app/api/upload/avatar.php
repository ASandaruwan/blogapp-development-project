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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['avatar'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No avatar image uploaded']);
    exit;
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Upload error code: ' . $file['error']]);
    exit;
}

if ($file['size'] > 3 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Avatar must be less than 3MB']);
    exit;
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!array_key_exists($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid image format. Allowed: JPG, PNG, WEBP']);
    exit;
}

$extension = $allowedTypes[$mimeType];
$fileName = 'avatar_' . getCurrentUserId() . '_' . time() . '.' . $extension;
$targetDir = __DIR__ . '/../../uploads/avatars';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetPath = $targetDir . '/' . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $avatarUrl = '/uploads/avatars/' . $fileName;
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE user SET avatar = ? WHERE id = ?");
        $stmt->execute([$avatarUrl, getCurrentUserId()]);
        
        echo json_encode([
            'success' => true,
            'url' => $avatarUrl,
            'full_url' => BASE_URL . $avatarUrl
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save avatar']);
}
