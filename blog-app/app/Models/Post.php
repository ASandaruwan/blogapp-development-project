<?php
namespace App\Models;

class Post {
    public static function findById(int $id): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT b.*, c.name AS category_name, c.slug AS category_slug, 
                   u.username, u.avatar, u.bio
            FROM blogPost b
            JOIN user u ON b.user_id = u.id
            LEFT JOIN category c ON b.category_id = c.id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT b.*, c.name AS category_name, c.slug AS category_slug, 
                   u.username, u.avatar, u.bio
            FROM blogPost b
            JOIN user u ON b.user_id = u.id
            LEFT JOIN category c ON b.category_id = c.id
            WHERE b.slug = ?
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo = getDBConnection();
        $slug = createSlug($data['title'], $pdo);
        $stmt = $pdo->prepare("
            INSERT INTO blogPost (user_id, category_id, title, slug, content, cover_image, status, views)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['category_id'] ?? null,
            $data['title'],
            $slug,
            $data['content'],
            $data['cover_image'] ?? null,
            $data['status'] ?? 'published'
        ]);
        return intval($pdo->lastInsertId());
    }

    public static function update(int $id, int $userId, array $data): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE blogPost 
            SET title = ?, content = ?, category_id = ?, cover_image = ?, status = ?
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['content'],
            $data['category_id'] ?? null,
            $data['cover_image'] ?? null,
            $data['status'] ?? 'published',
            $id,
            $userId
        ]);
    }

    public static function delete(int $id, int $userId): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM blogPost WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function incrementViews(int $id): void {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE blogPost SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
}
