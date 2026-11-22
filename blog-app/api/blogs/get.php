<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$currentUserId = getCurrentUserId();
$loggedIn = isLoggedIn();

// -------------------------------------------------------------
// 1. Single Blog by ID or Slug
// -------------------------------------------------------------
if (isset($_GET['id']) || isset($_GET['slug'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
    
    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT b.*, c.name AS category_name, c.slug AS category_slug, 
                   u.username, u.avatar, u.bio 
            FROM blogPost b 
            JOIN user u ON b.user_id = u.id 
            LEFT JOIN category c ON b.category_id = c.id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT b.*, c.name AS category_name, c.slug AS category_slug, 
                   u.username, u.avatar, u.bio 
            FROM blogPost b 
            JOIN user u ON b.user_id = u.id 
            LEFT JOIN category c ON b.category_id = c.id
            WHERE b.slug = ?
        ");
        $stmt->execute([$slug]);
    }
    
    $blog = $stmt->fetch();
    
    if (!$blog) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Blog not found']);
        exit;
    }
    
    // Check permission if draft
    if ($blog['status'] === 'draft' && (!$loggedIn || $currentUserId != $blog['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'This blog post is a draft and not publicly visible']);
        exit;
    }
    
    // Increment view count (only for published posts)
    if ($blog['status'] === 'published') {
        $incStmt = $pdo->prepare("UPDATE blogPost SET views = views + 1 WHERE id = ?");
        $incStmt->execute([$blog['id']]);
        $blog['views'] = intval($blog['views']) + 1;
    }
    
    // Fetch tags
    $tagsStmt = $pdo->prepare("
        SELECT t.name, t.slug 
        FROM tag t 
        JOIN post_tag pt ON t.id = pt.tag_id 
        WHERE pt.post_id = ?
    ");
    $tagsStmt->execute([$blog['id']]);
    $blog['tags'] = $tagsStmt->fetchAll();
    
    // Fetch likes count and user status
    $likesStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM post_like WHERE post_id = ?");
    $likesStmt->execute([$blog['id']]);
    $blog['like_count'] = intval($likesStmt->fetch()['total']);
    
    // Fetch comments count
    $commentsStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM comment WHERE post_id = ?");
    $commentsStmt->execute([$blog['id']]);
    $blog['comment_count'] = intval($commentsStmt->fetch()['total']);
    
    $blog['is_liked'] = false;
    $blog['is_bookmarked'] = false;
    $blog['is_owner'] = $loggedIn && ($currentUserId == $blog['user_id']);
    
    if ($loggedIn) {
        $likeCheck = $pdo->prepare("SELECT 1 FROM post_like WHERE post_id = ? AND user_id = ?");
        $likeCheck->execute([$blog['id'], $currentUserId]);
        $blog['is_liked'] = (bool)$likeCheck->fetch();
        
        $bmCheck = $pdo->prepare("SELECT 1 FROM post_bookmark WHERE post_id = ? AND user_id = ?");
        $bmCheck->execute([$blog['id'], $currentUserId]);
        $blog['is_bookmarked'] = (bool)$bmCheck->fetch();
    }
    
    $blog['avatar_url'] = getAvatarUrl($blog['avatar'], $blog['username']);
    $blog['cover_url'] = !empty($blog['cover_image']) ? BASE_URL . $blog['cover_image'] : null;
    
    echo json_encode(['success' => true, 'blog' => $blog]);
    exit;
}

// -------------------------------------------------------------
// 2. Blog Posts Listing with Multi-Filter & Search
// -------------------------------------------------------------
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 9;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$author = isset($_GET['author']) ? trim($_GET['author']) : '';
$onlyDrafts = isset($_GET['drafts']) && $_GET['drafts'] == '1';
$onlyBookmarked = isset($_GET['bookmarked']) && $_GET['bookmarked'] == '1';

$whereConditions = [];
$params = [];
$joins = "";

// Only show published by default, unless author requested their drafts
if ($onlyDrafts) {
    if (!$loggedIn) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'You must be logged in to view your drafts']);
        exit;
    }
    $whereConditions[] = "b.user_id = ? AND b.status = 'draft'";
    $params[] = $currentUserId;
} elseif ($onlyBookmarked) {
    if (!$loggedIn) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'You must be logged in to view your bookmarks']);
        exit;
    }
    $joins .= " JOIN post_bookmark pbm ON b.id = pbm.post_id AND pbm.user_id = ? ";
    $params[] = $currentUserId;
    $whereConditions[] = "b.status = 'published'";
} else {
    // Normal feed: show all published posts + author's own drafts if logged in
    if ($loggedIn) {
        $whereConditions[] = "(b.status = 'published' OR (b.user_id = ? AND b.status = 'draft'))";
        $params[] = $currentUserId;
    } else {
        $whereConditions[] = "b.status = 'published'";
    }
}

// Category filter
if (!empty($category)) {
    $whereConditions[] = "c.slug = ?";
    $params[] = $category;
}

// Tag filter
if (!empty($tag)) {
    $joins .= " JOIN post_tag pt_filter ON b.id = pt_filter.post_id JOIN tag t_filter ON pt_filter.tag_id = t_filter.id AND t_filter.slug = ? ";
    $params[] = $tag;
}

// Author filter
if (!empty($author)) {
    $whereConditions[] = "u.username = ?";
    $params[] = $author;
}

// Search filter
if (!empty($search)) {
    $whereConditions[] = "(b.title LIKE ? OR b.content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = count($whereConditions) > 0 ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total matching posts
$countSql = "
    SELECT COUNT(DISTINCT b.id) AS total
    FROM blogPost b
    JOIN user u ON b.user_id = u.id
    LEFT JOIN category c ON b.category_id = c.id
    $joins
    $whereClause
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = intval($countStmt->fetch()['total']);

// Fetch posts
$sql = "
    SELECT b.id, b.user_id, b.category_id, b.title, b.slug, b.content, 
           b.cover_image, b.status, b.views, b.created_at, b.updated_at,
           c.name AS category_name, c.slug AS category_slug, 
           u.username, u.avatar,
           (SELECT COUNT(*) FROM post_like pl WHERE pl.post_id = b.id) AS like_count,
           (SELECT COUNT(*) FROM comment cm WHERE cm.post_id = b.id) AS comment_count
    FROM blogPost b
    JOIN user u ON b.user_id = u.id
    LEFT JOIN category c ON b.category_id = c.id
    $joins
    $whereClause
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
";

$fetchParams = $params;
$fetchParams[] = $limit;
$fetchParams[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($fetchParams);
$blogs = $stmt->fetchAll();

// Attach tags & user flags for each blog post
$tagStmt = $pdo->prepare("
    SELECT t.name, t.slug 
    FROM tag t 
    JOIN post_tag pt ON t.id = pt.tag_id 
    WHERE pt.post_id = ?
");

foreach ($blogs as &$blog) {
    $blog['avatar_url'] = getAvatarUrl($blog['avatar'], $blog['username']);
    $blog['cover_url'] = !empty($blog['cover_image']) ? BASE_URL . $blog['cover_image'] : null;
    $blog['is_owner'] = $loggedIn && ($currentUserId == $blog['user_id']);
    
    $tagStmt->execute([$blog['id']]);
    $blog['tags'] = $tagStmt->fetchAll();
}

echo json_encode([
    'success' => true,
    'blogs' => $blogs,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'search' => $search,
    'category' => $category,
    'tag' => $tag,
    'author' => $author
]);
