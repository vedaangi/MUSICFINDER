<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION["user_id"])) {
        if (file_exists("login.php")) {
            header("Location: login.php");
        } else {
            header("Location: ../login.php");
        }
        exit();
    }
}

function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? 'User';
}

function getCurrentEmail() {
    return $_SESSION['email'] ?? '';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>
