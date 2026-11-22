<?php
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// Database configuration dynamically loaded from .env
if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME', 'blog_app'));
if (!defined('DB_USER')) define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Create or return singleton database connection
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        $isDev = env('APP_DEBUG', false) || env('APP_ENV', 'development') === 'development';
        $message = $isDev ? $e->getMessage() : "Database connection failed. Please contact the administrator.";
        throw new PDOException($message);
    }
}
