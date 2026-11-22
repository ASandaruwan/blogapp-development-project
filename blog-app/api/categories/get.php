<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Fetch all categories with published post count
    $sql = "
        SELECT c.*, COUNT(b.id) AS post_count
        FROM category c
        LEFT JOIN blogPost b ON c.id = b.category_id AND b.status = 'published'
        GROUP BY c.id
        ORDER BY c.name ASC
    ";
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load categories: ' . $e->getMessage()
    ]);
}
