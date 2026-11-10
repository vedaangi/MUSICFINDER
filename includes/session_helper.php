<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'alex_music';
}

function getCurrentUserId() {
    return (int)$_SESSION['user_id'];
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? 'User';
}
?>
