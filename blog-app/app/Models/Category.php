<?php
namespace App\Models;

class Category {
    public static function allWithCount(): array {
        $pdo = getDBConnection();
        $sql = "
            SELECT c.*, COUNT(b.id) AS post_count
            FROM category c
            LEFT JOIN blogPost b ON c.id = b.category_id AND b.status = 'published'
            GROUP BY c.id
            ORDER BY c.name ASC
        ";
        return $pdo->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM category WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM category WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
