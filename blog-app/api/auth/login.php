<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF Protection
    validateCsrfRequest();

    // 2. Check Login Rate Limiting (max 5 failed attempts per 15 mins)
    checkRateLimit('login', 5, 15);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, username, email, password FROM user WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Clear any rate-limiting flags
                clearFailedAttempts('login');

                // Prevent session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                // Regenerate CSRF token on auth change
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                echo json_encode(['success' => true, 'message' => 'Login successful']);
                exit;
            } else {
                recordFailedAttempt('login', 5, 15);
                $errors[] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "Login failed due to a server error. Please try again.";
        }
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

redirect(BASE_URL . '/login.php');
