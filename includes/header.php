<?php
require_once __DIR__ . '/session_helper.php';
$current_user = getCurrentUsername();
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MusicFinder</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav style="margin-bottom: 25px; padding: 14px 20px; background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; max-width: 1000px; margin: 0 auto 25px auto;">
        <div style="font-size: 1.25rem; font-weight: bold; color: #333;">
            <a href="index.php" style="color: #333; text-decoration: none;">🎵 MusicFinder</a>
        </div>
        <div>
            <a href="index.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Explore Songs</a>
            <a href="playlists.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Playlists</a>
            <a href="recommendations.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Recommendations</a>
            <a href="history.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">History</a>
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Dashboard</a>
                <a href="profile.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Profile (<?= htmlspecialchars($current_user) ?>)</a>
                <a href="logout.php" style="margin: 0 8px; color: #dc2626; font-weight: bold; text-decoration: none;">Logout</a>
            <?php else: ?>
                <a href="login.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Login</a>
                <a href="register.php" style="margin: 0 8px; color: #5b4bdb; font-weight: bold; text-decoration: none;">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <div style="max-width: 1000px; margin: 0 auto; text-align: left;">
