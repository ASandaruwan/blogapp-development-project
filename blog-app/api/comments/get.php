<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Post ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if post exists to know post author
    $postStmt = $pdo->prepare("SELECT user_id FROM blogPost WHERE id = ?");
    $postStmt->execute([$postId]);
    $post = $postStmt->fetch();
    
    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }
    
    $postAuthorId = $post['user_id'];
    $currentUserId = getCurrentUserId();
    
    // Fetch comments with author details
    $sql = "
        SELECT c.*, u.username, u.avatar
        FROM comment c
        JOIN user u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();
    
    foreach ($comments as &$comment) {
        $comment['avatar_url'] = getAvatarUrl($comment['avatar'], $comment['username']);
        $comment['can_delete'] = isLoggedIn() && ($currentUserId == $comment['user_id'] || $currentUserId == $postAuthorId);
    }
    
    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'total' => count($comments)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load comments: ' . $e->getMessage()]);
}
