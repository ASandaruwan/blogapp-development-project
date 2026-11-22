<?php
// Core Application Configuration & Security Foundation
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    $sessionLifetime = intval(env('SESSION_LIFETIME', 86400));

    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Base URL Resolution (Priority: .env -> auto-detect)
if (!defined('BASE_URL')) {
    $configuredUrl = env('BASE_URL');
    if (!empty($configuredUrl)) {
        define('BASE_URL', rtrim($configuredUrl, '/'));
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                     (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) 
                     ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        
        $basePath = (strpos($scriptDir, '/blog-app') !== false) ? '/blog-app' : '';
        define('BASE_URL', $protocol . '://' . $host . $basePath);
    }
}

// Timezone
date_default_timezone_set('UTC');

// Error Reporting & Debugging
$isDev = env('APP_DEBUG', true) || env('APP_ENV', 'development') === 'development';
if ($isDev) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Include Database & Class Autoloader
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/autoload.php';

// -------------------------------------------------------------
// CSRF Security Protection
// -------------------------------------------------------------
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function validateCsrfRequest() {
    if (!env('CSRF_PROTECTION', true)) {
        return true;
    }
    
    // Accept token from X-CSRF-TOKEN header or POST body
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Security token invalid or expired. Please refresh the page.',
            'csrf_error' => true
        ]);
        exit;
    }
    return true;
}

// -------------------------------------------------------------
// Rate Limiting (Brute-Force Attack Defense)
// -------------------------------------------------------------
function checkRateLimit($action = 'login', $maxAttempts = 5, $decayMinutes = 15) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_limit_{$action}_" . md5($ip);
    
    $lockedUntil = $_SESSION[$key]['locked_until'] ?? 0;
    if ($lockedUntil > time()) {
        $remainingSecs = $lockedUntil - time();
        $remainingMins = ceil($remainingSecs / 60);
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => "Too many attempts. Account locked temporarily for {$remainingMins} minute(s)."
        ]);
        exit;
    }
    
    if ($lockedUntil > 0 && $lockedUntil <= time()) {
        $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
    }
}

function recordFailedAttempt($action = 'login', $maxAttempts = 5, $decayMinutes = 15) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_limit_{$action}_" . md5($ip);
    
    $attempts = ($_SESSION[$key]['attempts'] ?? 0) + 1;
    $lockedUntil = 0;
    if ($attempts >= $maxAttempts) {
        $lockedUntil = time() + ($decayMinutes * 60);
    }
    $_SESSION[$key] = ['attempts' => $attempts, 'locked_until' => $lockedUntil];
}

function clearFailedAttempts($action = 'login') {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_limit_{$action}_" . md5($ip);
    unset($_SESSION[$key]);
}

// -------------------------------------------------------------
// Authentication & Authorization Helpers
// -------------------------------------------------------------
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function isBlogOwner($blogUserId) {
    return isLoggedIn() && $_SESSION['user_id'] == $blogUserId;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Sanitize Output against XSS
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Clean URL router helper
function url($route = '') {
    $route = ltrim($route, '/');
    return BASE_URL . ($route ? '/' . $route : '');
}

// Asset URL with cache-busting timestamp
function assetUrl($path) {
    $fullPath = __DIR__ . '/..' . $path;
    $version = file_exists($fullPath) ? filemtime($fullPath) : time();
    return BASE_URL . $path . '?v=' . $version;
}

// Unique SEO-Friendly Slug Generator
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

// Author Avatar Fallback URL
function getAvatarUrl($avatarPath, $username = 'User') {
    if (!empty($avatarPath)) {
        if (strpos($avatarPath, 'http') === 0) {
            return $avatarPath;
        }
        return BASE_URL . $avatarPath;
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=3b82f6&color=ffffff&bold=true';
}
