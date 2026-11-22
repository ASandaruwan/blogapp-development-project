<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $coverImage = isset($_POST['cover_image']) ? trim($_POST['cover_image']) : null;
    $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';
    $tagsRaw = trim($_POST['tags'] ?? '');
    
    $errors = [];
    
    if (empty($id)) {
        $errors[] = "Blog ID is required";
    }
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if blog exists and user owns it
            $stmt = $pdo->prepare("SELECT user_id, title, slug, cover_image FROM blogPost WHERE id = ?");
            $stmt->execute([$id]);
            $blog = $stmt->fetch();
            
            if (!$blog) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Blog not found']);
                exit;
            }
            
            if (!isBlogOwner($blog['user_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => "You don't have permission to edit this blog"]);
                exit;
            }
            
            // Generate/update slug if needed
            $slug = $blog['slug'];
            if (empty($slug) || $blog['title'] !== $title) {
                $slug = createSlug($title, $pdo, $id);
            }
            
            // If cover image not sent or empty string and wasn't changed
            $finalCoverImage = ($coverImage !== null && $coverImage !== '') ? $coverImage : $blog['cover_image'];
            if ($coverImage === 'remove') {
                $finalCoverImage = null;
            }
            
            $updateStmt = $pdo->prepare("
                UPDATE blogPost 
                SET title = ?, slug = ?, content = ?, category_id = ?, cover_image = ?, status = ?
                WHERE id = ? AND user_id = ?
            ");
            $updateStmt->execute([$title, $slug, $content, $categoryId, $finalCoverImage, $status, $id, getCurrentUserId()]);
            
            // Sync tags
            $delTagsStmt = $pdo->prepare("DELETE FROM post_tag WHERE post_id = ?");
            $delTagsStmt->execute([$id]);
            
            if (!empty($tagsRaw)) {
                $tags = array_unique(array_filter(array_map('trim', explode(',', $tagsRaw))));
                foreach ($tags as $tagName) {
                    if (empty($tagName)) continue;
                    $tagSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName), '-'));
                    if (empty($tagSlug)) continue;
                    
                    $tagStmt = $pdo->prepare("INSERT IGNORE INTO tag (name, slug) VALUES (?, ?)");
                    $tagStmt->execute([$tagName, $tagSlug]);
                    
                    $findTagStmt = $pdo->prepare("SELECT id FROM tag WHERE slug = ?");
                    $findTagStmt->execute([$tagSlug]);
                    $tagRecord = $findTagStmt->fetch();
                    
                    if ($tagRecord) {
                        $linkStmt = $pdo->prepare("INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (?, ?)");
                        $linkStmt->execute([$id, $tagRecord['id']]);
                    }
                }
            }
            
            $msg = ($status === 'draft') ? 'Draft updated successfully!' : 'Story updated successfully!';
            echo json_encode([
                'success' => true,
                'message' => $msg,
                'id' => $id,
                'slug' => $slug,
                'status' => $status
            ]);
            exit;
        } catch (PDOException $e) {
            $errors[] = "Failed to update blog: " . $e->getMessage();
        }
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Invalid request method']);
