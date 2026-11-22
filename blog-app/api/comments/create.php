<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to comment']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$postId = intval($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Post ID is required']);
    exit;
}

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
    exit;
}

if (strlen($content) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Comment cannot exceed 1000 characters']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Ensure post exists
    $checkStmt = $pdo->prepare("SELECT id FROM blogPost WHERE id = ?");
    $checkStmt->execute([$postId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO comment (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$postId, getCurrentUserId(), $content]);
    $commentId = $pdo->lastInsertId();
    
    // Fetch newly created comment with user info
    $fetchStmt = $pdo->prepare("
        SELECT c.*, u.username, u.avatar
        FROM comment c
        JOIN user u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $fetchStmt->execute([$commentId]);
    $newComment = $fetchStmt->fetch();
    $newComment['avatar_url'] = getAvatarUrl($newComment['avatar'], $newComment['username']);
    $newComment['can_delete'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment posted successfully',
        'comment' => $newComment
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to post comment: ' . $e->getMessage()]);
}
