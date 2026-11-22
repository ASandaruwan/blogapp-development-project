<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $username = trim($_GET['username'] ?? '');
        $userId = intval($_GET['id'] ?? 0);
        
        if (empty($username) && !$userId) {
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User not specified']);
                exit;
            }
        }
        
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT id, username, email, avatar, bio, created_at FROM user WHERE username = ?");
            $stmt->execute([$username]);
        } else {
            $stmt = $pdo->prepare("SELECT id, username, email, avatar, bio, created_at FROM user WHERE id = ?");
            $stmt->execute([$userId]);
        }
        
        $user = $stmt->fetch();
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        $isSelf = isLoggedIn() && getCurrentUserId() == $user['id'];
        if (!$isSelf) {
            unset($user['email']); // Privacy
        }
        
        $user['avatar_url'] = getAvatarUrl($user['avatar'], $user['username']);
        
        // Calculate user stats
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_posts,
                COALESCE(SUM(views), 0) AS total_views
            FROM blogPost 
            WHERE user_id = ? AND status = 'published'
        ");
        $statsStmt->execute([$user['id']]);
        $stats = $statsStmt->fetch();
        
        // Count total likes received
        $likesStmt = $pdo->prepare("
            SELECT COUNT(*) AS total_likes
            FROM post_like pl
            JOIN blogPost b ON pl.post_id = b.id
            WHERE b.user_id = ?
        ");
        $likesStmt->execute([$user['id']]);
        $likes = $likesStmt->fetch();
        
        $user['stats'] = [
            'total_posts' => intval($stats['total_posts']),
            'total_views' => intval($stats['total_views']),
            'total_likes' => intval($likes['total_likes'])
        ];
        $user['is_self'] = $isSelf;
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Validate CSRF Protection
        validateCsrfRequest();
        
        $bio = trim($_POST['bio'] ?? '');
        $userId = getCurrentUserId();
        
        $updateStmt = $pdo->prepare("UPDATE user SET bio = ? WHERE id = ?");
        $updateStmt->execute([$bio, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'bio' => $bio
        ]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
