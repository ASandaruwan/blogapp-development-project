<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to bookmark stories']);
    exit;
}

// Validate CSRF Protection
validateCsrfRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$postId = intval($_POST['id'] ?? 0);
if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Post ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    $userId = getCurrentUserId();
    
    // Check if bookmark exists
    $checkStmt = $pdo->prepare("SELECT 1 FROM post_bookmark WHERE post_id = ? AND user_id = ?");
    $checkStmt->execute([$postId, $userId]);
    $bookmarked = false;
    
    if ($checkStmt->fetch()) {
        // Remove bookmark
        $delStmt = $pdo->prepare("DELETE FROM post_bookmark WHERE post_id = ? AND user_id = ?");
        $delStmt->execute([$postId, $userId]);
        $bookmarked = false;
        $message = 'Removed from bookmarks';
    } else {
        // Add bookmark
        $insStmt = $pdo->prepare("INSERT INTO post_bookmark (post_id, user_id) VALUES (?, ?)");
        $insStmt->execute([$postId, $userId]);
        $bookmarked = true;
        $message = 'Saved to your bookmarks';
    }
    
    echo json_encode([
        'success' => true,
        'bookmarked' => $bookmarked,
        'message' => $message
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
