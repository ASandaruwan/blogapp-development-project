<?php
namespace App\Controllers;

use App\Core\Response;
use App\Models\Post;

class PostController {
    public function index(): void {
        $pdo = getDBConnection();
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 9)));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("
            SELECT b.*, c.name AS category_name, c.slug AS category_slug, u.username, u.avatar,
                   (SELECT COUNT(*) FROM post_like pl WHERE pl.post_id = b.id) AS like_count,
                   (SELECT COUNT(*) FROM comment cm WHERE cm.post_id = b.id) AS comment_count
            FROM blogPost b
            JOIN user u ON b.user_id = u.id
            LEFT JOIN category c ON b.category_id = c.id
            WHERE b.status = 'published'
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['avatar_url'] = getAvatarUrl($post['avatar'], $post['username']);
            $post['cover_url'] = !empty($post['cover_image']) ? BASE_URL . $post['cover_image'] : null;
        }

        Response::success(['posts' => $posts, 'page' => $page, 'limit' => $limit]);
    }

    public function show(array $params): void {
        $identifier = $params['slug'] ?? $params['id'] ?? '';
        $post = is_numeric($identifier) ? Post::findById(intval($identifier)) : Post::findBySlug($identifier);

        if (!$post) {
            Response::error('Post not found', 404);
        }

        if ($post['status'] === 'published') {
            Post::incrementViews(intval($post['id']));
            $post['views'] = intval($post['views']) + 1;
        }

        $post['avatar_url'] = getAvatarUrl($post['avatar'], $post['username']);
        $post['cover_url'] = !empty($post['cover_image']) ? BASE_URL . $post['cover_image'] : null;

        Response::success(['post' => $post]);
    }

    public function store(): void {
        if (!isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        validateCsrfRequest();

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (empty($title) || empty($content)) {
            Response::error('Title and content are required', 400);
        }

        $id = Post::create([
            'user_id'     => getCurrentUserId(),
            'title'       => $title,
            'content'     => $content,
            'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'cover_image' => $_POST['cover_image'] ?? null,
            'status'      => $_POST['status'] ?? 'published'
        ]);

        Response::success(['id' => $id], 'Post created successfully', 201);
    }

    public function update(array $params): void {
        if (!isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        validateCsrfRequest();

        $id = intval($params['id'] ?? 0);
        $post = Post::findById($id);

        if (!$post) {
            Response::error('Post not found', 404);
        }

        if (!isBlogOwner($post['user_id'])) {
            Response::error('Forbidden', 403);
        }

        $title = trim($_POST['title'] ?? $post['title']);
        $content = trim($_POST['content'] ?? $post['content']);

        Post::update($id, getCurrentUserId(), [
            'title'       => $title,
            'content'     => $content,
            'category_id' => $_POST['category_id'] ?? $post['category_id'],
            'cover_image' => $_POST['cover_image'] ?? $post['cover_image'],
            'status'      => $_POST['status'] ?? $post['status']
        ]);

        Response::success(['id' => $id], 'Post updated successfully');
    }

    public function destroy(array $params): void {
        if (!isLoggedIn()) {
            Response::error('Unauthorized', 401);
        }
        validateCsrfRequest();

        $id = intval($params['id'] ?? 0);
        $post = Post::findById($id);

        if (!$post) {
            Response::error('Post not found', 404);
        }

        if (!isBlogOwner($post['user_id'])) {
            Response::error('Forbidden', 403);
        }

        Post::delete($id, getCurrentUserId());
        Response::success([], 'Post deleted successfully');
    }
}
