<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Protection
    validateCsrfRequest();

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, hyphens, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Username or email already exists";
            } else {
                // Hash password and insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO user (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword]);
                
                // Prevent session fixation
                session_regenerate_id(true);

                // Auto login after registration
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Registration failed due to a server error. Please try again.";
        }
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

redirect(BASE_URL . '/register.php');
