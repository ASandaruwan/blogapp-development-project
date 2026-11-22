<?php
// Built-in PHP Development Server Router for Clean Extensionless URLs

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// Serve existing static files directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Security: Block sensitive files (.env, .git, .sql)
if (preg_match('/\.(env|sql|git)/i', $uri)) {
    http_response_code(403);
    echo "403 Forbidden";
    exit;
}

// RESTful API v1
if (preg_match('#^/api/v1/#', $uri)) {
    require __DIR__ . '/api/v1/index.php';
    exit;
}

// Clean post routes: /posts/<slug> or /post/<slug>
if (preg_match('#^/posts?/([a-zA-Z0-9_-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/blog.php';
    exit;
}

// Clean profile routes: /profile/<username>
if (preg_match('#^/profile/([a-zA-Z0-9_-]+)/?$#', $uri, $matches)) {
    $_GET['username'] = $matches[1];
    require __DIR__ . '/profile.php';
    exit;
}

// Clean editor routes: /editor/<id>
if (preg_match('#^/editor/([0-9]+)/?$#', $uri, $matches)) {
    $_GET['id'] = intval($matches[1]);
    require __DIR__ . '/editor.php';
    exit;
}

// Extensionless PHP routing: /login -> login.php, /dashboard -> dashboard.php
$phpFile = __DIR__ . $uri . '.php';
if (file_exists($phpFile)) {
    require $phpFile;
    exit;
}

// Default to index.php
require __DIR__ . '/index.php';
