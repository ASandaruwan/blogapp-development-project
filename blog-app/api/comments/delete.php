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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$commentId = intval($_POST['id'] ?? 0);
if (!$commentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Comment ID is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Fetch comment and associated post author
    $stmt = $pdo->prepare("
        SELECT c.id, c.user_id, b.user_id AS post_author_id
        FROM comment c
        JOIN blogPost b ON c.post_id = b.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
        exit;
    }
    
    $currentUserId = getCurrentUserId();
    // Allow if user is comment author OR post author
    if ($currentUserId != $comment['user_id'] && $currentUserId != $comment['post_author_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to delete this comment']);
        exit;
    }
    
    $deleteStmt = $pdo->prepare("DELETE FROM comment WHERE id = ?");
    $deleteStmt->execute([$commentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment deleted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete comment: ' . $e->getMessage()]);
}
