<?php
namespace App\Controllers;

use App\Core\Response;
use App\Models\User;

class AuthController {
    public function login(): void {
        validateCsrfRequest();
        checkRateLimit('login', 5, 15);

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            Response::error('Email and password are required', 400);
        }

        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            clearFailedAttempts('login');
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            Response::success(['user' => ['username' => $user['username']]], 'Login successful');
        }

        recordFailedAttempt('login', 5, 15);
        Response::error('Invalid email or password', 401);
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        redirect(BASE_URL . '/login');
    }
}
