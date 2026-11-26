<?php
require_once __DIR__ . '/session_helper.php';
$is_logged_in = isLoggedIn();
$current_user_id = getCurrentUserId();
$current_user = getCurrentUsername();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicFinder - Music Discovery & Playlists</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="app-sidebar">
            <div class="brand">
                <a href="index.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-primary);">
                    <i class="fa-solid fa-compact-disc brand-icon"></i>
                    <span>MusicFinder</span>
                </a>
                <button class="mobile-close-btn" onclick="toggleMobileSidebar()" style="display: none; background: none; border: none; color: var(--text-secondary); font-size: 1.2rem; cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <nav class="nav-menu">
                <a href="index.php" class="nav-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-house"></i>
                    <span>Home & Discover</span>
                </a>
                <a href="catalog.php" class="nav-item <?= $current_page == 'catalog.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Catalog & Search</span>
                </a>
                <a href="playlists.php" class="nav-item <?= in_array($current_page, ['playlists.php', 'playlist_view.php']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-ul"></i>
                    <span>My Playlists</span>
                </a>
                <a href="favorites.php" class="nav-item <?= $current_page == 'favorites.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-heart"></i>
                    <span>Liked Songs</span>
                </a>
                <a href="recommendations.php" class="nav-item <?= $current_page == 'recommendations.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span>Recommendations</span>
                </a>
                <a href="history.php" class="nav-item <?= $current_page == 'history.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Listening History</span>
                </a>
                <a href="profile.php" class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-circle-user"></i>
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="user-pill-container" style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <?php if ($is_logged_in): ?>
                    <div class="user-pill" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--accent-light); border: 1px solid var(--border-color); border-radius: var(--radius);">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; min-width: 0; flex: 1;">
                            <i class="fa-solid fa-circle-user" style="font-size: 1.6rem; color: var(--accent);"></i>
                            <div class="user-info" style="overflow: hidden;">
                                <span class="user-name" style="display: block; font-weight: 600; font-size: 0.9rem; color: var(--text-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                    <?= htmlspecialchars($current_user) ?>
                                </span>
                                <span class="user-role" style="font-size: 0.75rem; color: var(--accent); font-weight: 600;">Online</span>
                            </div>
                        </a>
                        <a href="logout.php" title="Sign Out" style="color: var(--text-muted); font-size: 0.95rem; padding: 6px 8px; border-radius: 6px; transition: color 0.2s;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; gap: 8px;">
                        <a href="login.php" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center;">Sign In</a>
                        <a href="register.php" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center;">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="top-nav">
                <div class="top-nav-left" style="display: flex; align-items: center; gap: 14px;">
                    <button class="mobile-toggle-btn" onclick="toggleMobileSidebar()" style="display: none; background: #fff; border: 1px solid var(--border-color); color: var(--text-primary); width: 36px; height: 36px; border-radius: var(--radius); cursor: pointer; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <span class="page-greeting">Personalized Music Discovery & Playlist Platform</span>
                </div>
                
                <div class="top-nav-right" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <!-- Demo Switcher Quick Access (for college presentation) -->
                    <?php if ($is_logged_in): ?>
                        <div class="demo-badge-dropdown" style="display: inline-flex; align-items: center; gap: 6px; background: #fdf2f8; border: 1px solid var(--border-subtle); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem;">
                            <i class="fa-solid fa-flask" style="color: var(--accent);"></i>
                            <span style="color: var(--text-secondary);">Demo: <strong style="color: var(--accent);"><?= htmlspecialchars($current_user) ?></strong></span>
                            <form method="POST" action="login.php" style="display: inline; margin-left: 4px;">
                                <input type="hidden" name="demo_user_id" value="<?= $current_user_id === 1 ? 2 : 1 ?>">
                                <button type="submit" style="background: none; border: none; color: var(--accent-hover); text-decoration: underline; cursor: pointer; font-size: 0.78rem; font-weight: 600;">
                                    Switch to <?= $current_user_id === 1 ? 'Sarah' : 'Alex' ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <a href="playlists.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> New Playlist
                    </a>
                </div>
            </header>
            
            <div class="page-body">
