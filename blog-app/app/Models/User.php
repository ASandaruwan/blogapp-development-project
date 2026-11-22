<?php
namespace App\Models;

class User {
    public static function findById(int $id): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, role, avatar, bio, created_at FROM user WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, role, avatar, bio, created_at FROM user WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $username, string $email, string $password): int {
        $pdo = getDBConnection();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed]);
        return intval($pdo->lastInsertId());
    }

    public static function updateBio(int $userId, string $bio): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE user SET bio = ? WHERE id = ?");
        return $stmt->execute([$bio, $userId]);
    }

    public static function updateAvatar(int $userId, string $avatarUrl): bool {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE user SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarUrl, $userId]);
    }
}
