<?php
// Application configuration
session_start();

// Base URL - Update this for your hosting environment
// Option 1: Auto-detect (recommended for production)
// Option 2: Manually set it (uncomment and set your URL)
if (!defined('BASE_URL')) {
    // Check if we're on the production server
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, '42web.io') !== false || strpos($host, 'asj-blog-app') !== false) {
        define('BASE_URL', 'http://asj-blog-app.42web.io');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                     (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) 
                     ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        
        // Check if we're in a subdirectory (like /blog-app for local dev)
        if (strpos($scriptDir, '/blog-app') !== false) {
            $basePath = '/blog-app';
        } else {
            // For production, usually files are in root, so no subdirectory
            $basePath = '';
        }
        
        define('BASE_URL', $protocol . '://' . $host . $basePath);
    }
}

// Timezone
date_default_timezone_set('UTC');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/database.php';

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to check if user owns a blog post
function isBlogOwner($blogUserId) {
    return isLoggedIn() && $_SESSION['user_id'] == $blogUserId;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to sanitize output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Helper function to get asset URL with cache busting
function assetUrl($path) {
    $fullPath = __DIR__ . '/..' . $path;
    $version = file_exists($fullPath) ? filemtime($fullPath) : time();
    return BASE_URL . $path . '?v=' . $version;
}

// Helper to generate unique SEO-friendly slug
function createSlug($title, $pdo, $postId = null) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'story';
    }
    
    $originalSlug = $slug;
    $count = 1;
    while (true) {
        $sql = "SELECT id FROM blogPost WHERE slug = ?" . ($postId ? " AND id != ?" : "");
        $params = $postId ? [$slug, $postId] : [$slug];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            break;
        }
        $count++;
        $slug = $originalSlug . '-' . $count;
    }
    return $slug;
}

// Helper to get user avatar URL
function getAvatarUrl($avatarPath, $username = 'User') {
    if (!empty($avatarPath)) {
        if (strpos($avatarPath, 'http') === 0) {
            return $avatarPath;
        }
        return BASE_URL . $avatarPath;
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=3b82f6&color=ffffff&bold=true';
}


