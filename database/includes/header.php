<?php
require_once __DIR__ . '/session_helper.php';
$current_user = getCurrentUsername();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicFinder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-compact-disc brand-icon"></i>
                <span>MusicFinder</span>
            </div>
            
            <nav class="nav-menu">
                <a href="index.php" class="nav-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-house"></i>
                    <span>Home & Discover</span>
                </a>
                <a href="playlists.php" class="nav-item <?= in_array($current_page, ['playlists.php', 'playlist_view.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-ul"></i>
                    <span>My Playlists</span>
                </a>
                <a href="recommendations.php" class="nav-item <?= $current_page == 'recommendations.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Recommendations</span>
                </a>
                <a href="history.php" class="nav-item <?= $current_page == 'history.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Listening History</span>
                </a>
            </nav>

            <div class="user-pill">
                <i class="fa-solid fa-circle-user"></i>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($current_user) ?></span>
                    <span class="user-role">Member 3 Module</span>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-nav">
                <div class="top-nav-left">
                    <span class="page-greeting">Personalized Music Discovery & Playlist Platform</span>
                </div>
                <div class="top-nav-right">
                    <a href="playlists.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> New Playlist
                    </a>
                </div>
            </header>
            
            <div class="page-body">
