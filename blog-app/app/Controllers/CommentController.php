<?php
namespace App\Controllers;

use App\Core\Response;
use App\Models\Comment;
use App\Models\Post;

class CommentController {
    public function index(): void {
        $postId = intval($_GET['post_id'] ?? 0);
        if (!$postId) {
            Response::error('Post ID is required', 400);
        }

        $comments = Comment::getByPostId($postId);
        $currentUserId = getCurrentUserId();
        $post = Post::findById($postId);
        $postAuthorId = $post['user_id'] ?? 0;

        foreach ($comments as &$comment) {
            $comment['avatar_url'] = getAvatarUrl($comment['avatar'], $comment['username']);
            $comment['can_delete'] = isLoggedIn() && ($currentUserId == $comment['user_id'] || $currentUserId == $postAuthorId);
        }

        Response::success(['comments' => $comments, 'total' => count($comments)]);
    }

    public function store(): void {
        if (!isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        validateCsrfRequest();

        $postId = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if (!$postId || empty($content)) {
            Response::error('Post ID and content are required', 400);
        }

        $commentId = Comment::create($postId, getCurrentUserId(), $content);
        Response::success(['id' => $commentId], 'Comment created successfully', 201);
    }

    public function destroy(array $params): void {
        if (!isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        validateCsrfRequest();

        $commentId = intval($params['id'] ?? 0);
        if (!$commentId) {
            Response::error('Comment ID is required', 400);
        }

        $deleted = Comment::delete($commentId, getCurrentUserId());
        if (!$deleted) {
            Response::error('Permission denied or comment not found', 403);
        }

        Response::success([], 'Comment deleted successfully');
    }
}
