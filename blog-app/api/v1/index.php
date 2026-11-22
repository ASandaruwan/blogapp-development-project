<?php
require_once __DIR__ . '/../../config/config.php';

use App\Core\Router;
use App\Controllers\PostController;
use App\Controllers\CommentController;
use App\Controllers\AuthController;

// Handle CORS
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// RESTful API v1 Routes
Router::get('api/v1/posts', [PostController::class, 'index']);
Router::get('api/v1/posts/{id}', [PostController::class, 'show']);
Router::post('api/v1/posts', [PostController::class, 'store']);
Router::put('api/v1/posts/{id}', [PostController::class, 'update']);
Router::delete('api/v1/posts/{id}', [PostController::class, 'destroy']);

Router::get('api/v1/comments', [CommentController::class, 'index']);
Router::post('api/v1/comments', [CommentController::class, 'store']);
Router::delete('api/v1/comments/{id}', [CommentController::class, 'destroy']);

Router::post('api/v1/auth/login', [AuthController::class, 'login']);
Router::post('api/v1/auth/logout', [AuthController::class, 'logout']);

// Dispatch Current Request
Router::dispatch($_SERVER['REQUEST_URI'] ?? '', $_SERVER['REQUEST_METHOD'] ?? 'GET');
