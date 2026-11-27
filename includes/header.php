<?php
require_once __DIR__ . '/session_helper.php';
require_once __DIR__ . '/../config/db.php';

$current_user = getCurrentUsername();
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isLoggedIn();
$is_admin = false;
$has_premium = false;

if ($is_logged_in) {
    $uid = getCurrentUserId();
    
    // Check admin status
    if (isset($_SESSION['role'])) {
        $is_admin = ($_SESSION['role'] === 'admin');
    } else {
        $chkAdmin = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        if ($chkAdmin) {
            $chkAdmin->bind_param('i', $uid);
            $chkAdmin->execute();
            $res = $chkAdmin->get_result()->fetch_assoc();
            $chkAdmin->close();
            if ($res && ($res['role'] ?? '') === 'admin') {
                $is_admin = true;
                $_SESSION['role'] = 'admin';
            }
        }
    }

    // Check active subscription
    $today = date('Y-m-d');
    $subChk = $conn->prepare("SELECT subscription_id FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= ? LIMIT 1");
    if ($subChk) {
        $subChk->bind_param('is', $uid, $today);
        $subChk->execute();
        $subRes = $subChk->get_result()->fetch_assoc();
        $subChk->close();
        if ($subRes) {
            $has_premium = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VARP | Editorial Music Mood Board</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar-custom" aria-label="Main Navigation">
            <a href="index.php" class="nav-brand" title="VARP Home">
                <i class="fa-solid fa-record-vinyl" style="color: var(--accent-gold);"></i>
                <span>VARP</span>
            </a>
            
            <div class="nav-links">
                <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-magnifying-glass"></i> Explore
                </a>
                <a href="playlists.php" class="<?= $current_page === 'playlists.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-ul"></i> Playlists
                </a>
                <a href="recommendations.php" class="<?= $current_page === 'recommendations.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-sparkles"></i> For You
                </a>
                <a href="history.php" class="<?= $current_page === 'history.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> History
                </a>
                
                <?php if ($is_logged_in): ?>
                    <a href="<?= $has_premium ? 'premium_dashboard.php' : 'subscribe.php' ?>" class="<?= in_array($current_page, ['premium_dashboard.php', 'subscribe.php']) ? 'active' : '' ?> nav-highlight">
                        <i class="fa-solid fa-crown"></i> <?= $has_premium ? 'Premium Hub' : 'Upgrade ₹99' ?>
                    </a>
                    <a href="top_artists.php" class="<?= $current_page === 'top_artists.php' ? 'active' : '' ?>" title="Top 5 Artists">
                        <i class="fa-solid fa-ranking-star"></i> Top Artists
                    </a>
                    <a href="music_personality.php" class="<?= $current_page === 'music_personality.php' ? 'active' : '' ?>" title="Music Personality">
                        <i class="fa-solid fa-masks-theater"></i> Personality
                    </a>
                    <a href="character_recommendation.php" class="<?= $current_page === 'character_recommendation.php' ? 'active' : '' ?>" title="Character Match">
                        <i class="fa-solid fa-user-astronaut"></i> Character
                    </a>
                    <a href="weekly_feature.php" class="<?= $current_page === 'weekly_feature.php' ? 'active' : '' ?>" title="Weekly Movie & Character">
                        <i class="fa-solid fa-clapperboard"></i> Weekly
                    </a>
                    <a href="download.php" class="<?= $current_page === 'download.php' ? 'active' : '' ?>" title="Eligible Downloads">
                        <i class="fa-solid fa-download"></i> Downloads
                    </a>

                    <?php if ($is_admin): ?>
                        <a href="admin_weekly.php" class="<?= $current_page === 'admin_weekly.php' ? 'active' : '' ?>" style="color: var(--forest-100); border: 1px dashed var(--border-forest);">
                            <i class="fa-solid fa-shield-halved"></i> Admin
                        </a>
                    <?php endif; ?>

                    <a href="profile.php" class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($current_user) ?>
                    </a>
                    <a href="logout.php" class="nav-danger" title="Sign Out">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="<?= $current_page === 'login.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </a>
                    <a href="register.php" class="<?= $current_page === 'register.php' ? 'active' : '' ?> nav-highlight">
                        <i class="fa-solid fa-user-plus"></i> Join Free
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main style="max-width: 1100px; margin: 0 auto; text-align: left;">
