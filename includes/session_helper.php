<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(bool $jsonResponse = false): int {
        if (!isLoggedIn()) {
            if ($jsonResponse) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Authentication required.'
                ]);
                exit;
            }
            header("Location: login.php");
            exit;
        }
        return (int)$_SESSION['user_id'];
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId(): int {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    }
}

if (!function_exists('getCurrentUsername')) {
    function getCurrentUsername(): string {
        return $_SESSION['username'] ?? 'User';
    }
}

if (!function_exists('getCurrentEmail')) {
    function getCurrentEmail(): string {
        return $_SESSION['email'] ?? '';
    }
}
?>