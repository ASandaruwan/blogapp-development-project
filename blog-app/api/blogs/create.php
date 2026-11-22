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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $coverImage = !empty($_POST['cover_image']) ? trim($_POST['cover_image']) : null;
    $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';
    $tagsRaw = trim($_POST['tags'] ?? '');
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Generate unique slug
            $slug = createSlug($title, $pdo);
            
            $stmt = $pdo->prepare("
                INSERT INTO blogPost (user_id, category_id, title, slug, content, cover_image, status, views)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([getCurrentUserId(), $categoryId, $title, $slug, $content, $coverImage, $status]);
            $blogId = $pdo->lastInsertId();
            
            // Handle tags
            if (!empty($tagsRaw)) {
                $tags = array_unique(array_filter(array_map('trim', explode(',', $tagsRaw))));
                foreach ($tags as $tagName) {
                    if (empty($tagName)) continue;
                    $tagSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName), '-'));
                    if (empty($tagSlug)) continue;
                    
                    // Insert tag if not exists
                    $tagStmt = $pdo->prepare("INSERT IGNORE INTO tag (name, slug) VALUES (?, ?)");
                    $tagStmt->execute([$tagName, $tagSlug]);
                    
                    // Get tag ID
                    $findTagStmt = $pdo->prepare("SELECT id FROM tag WHERE slug = ?");
                    $findTagStmt->execute([$tagSlug]);
                    $tagRecord = $findTagStmt->fetch();
                    
                    if ($tagRecord) {
                        $linkStmt = $pdo->prepare("INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (?, ?)");
                        $linkStmt->execute([$blogId, $tagRecord['id']]);
                    }
                }
            }
            
            $msg = ($status === 'draft') ? 'Draft saved successfully!' : 'Story published successfully!';
            echo json_encode([
                'success' => true,
                'message' => $msg,
                'id' => $blogId,
                'slug' => $slug,
                'status' => $status
            ]);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Failed to create blog: " . $e->getMessage();
        }
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
