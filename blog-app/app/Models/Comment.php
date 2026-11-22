<?php
namespace App\Models;

class Comment {
    public static function getByPostId(int $postId): array {
        $pdo = getDBConnection();
        $sql = "
            SELECT c.*, u.username, u.avatar
            FROM comment c
            JOIN user u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public static function create(int $postId, int $userId, string $content): int {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO comment (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $userId, $content]);
        return intval($pdo->lastInsertId());
    }

    public static function delete(int $commentId, int $userId): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            DELETE c FROM comment c
            JOIN blogPost b ON c.post_id = b.id
            WHERE c.id = ? AND (c.user_id = ? OR b.user_id = ?)
        ");
        return $stmt->execute([$commentId, $userId, $userId]);
    }
}
