<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to like this post']);
    exit;
}

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
    
    // Check if like exists
    $checkStmt = $pdo->prepare("SELECT 1 FROM post_like WHERE post_id = ? AND user_id = ?");
    $checkStmt->execute([$postId, $userId]);
    $liked = false;
    
    if ($checkStmt->fetch()) {
        // Unlike
        $delStmt = $pdo->prepare("DELETE FROM post_like WHERE post_id = ? AND user_id = ?");
        $delStmt->execute([$postId, $userId]);
        $liked = false;
    } else {
        // Like
        $insStmt = $pdo->prepare("INSERT INTO post_like (post_id, user_id) VALUES (?, ?)");
        $insStmt->execute([$postId, $userId]);
        $liked = true;
    }
    
    // Get updated count
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM post_like WHERE post_id = ?");
    $countStmt->execute([$postId]);
    $likeCount = intval($countStmt->fetch()['total']);
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'count' => $likeCount
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
