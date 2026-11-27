<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Fetch quick stats for the user
$playlistCount = 0;
$pRes = $conn->prepare("SELECT COUNT(*) as cnt FROM playlists WHERE user_id = ?");
if ($pRes) {
    $pRes->bind_param("i", $user_id);
    $pRes->execute();
    $playlistCount = (int)$pRes->get_result()->fetch_assoc()['cnt'];
    $pRes->close();
}

$historyCount = 0;
$hRes = $conn->prepare("SELECT COUNT(*) as cnt FROM listening_history WHERE user_id = ?");
if ($hRes) {
    $hRes->bind_param("i", $user_id);
    $hRes->execute();
    $historyCount = (int)$hRes->get_result()->fetch_assoc()['cnt'];
    $hRes->close();
}

$today = date('Y-m-d');
$subRes = $conn->prepare("SELECT plan, end_date FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= ? LIMIT 1");
$subData = null;
if ($subRes) {
    $subRes->bind_param("is", $user_id, $today);
    $subRes->execute();
    $subData = $subRes->get_result()->fetch_assoc();
    $subRes->close();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome, <?= htmlspecialchars($username) ?></h1>
        <p class="page-subtitle">Your curated mood board and listening overview in VARP.</p>
    </div>
    <div>
        <?php if ($subData): ?>
            <span class="status-pill status-active">
                <i class="fa-solid fa-crown"></i> Premium Active (Expires: <?= htmlspecialchars($subData['end_date']) ?>)
            </span>
        <?php else: ?>
            <a href="subscribe.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-bolt"></i> Upgrade to Premium (₹99/mo)
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="two-col-grid" style="margin-bottom: 28px;">
    <div class="card" style="padding: 24px;">
        <div class="playlist-card-top">
            <div class="playlist-icon"><i class="fa-solid fa-headphones"></i></div>
            <span style="font-size: 1.8rem; font-weight: 800; color: var(--forest-100);"><?= $historyCount ?></span>
        </div>
        <h3 class="playlist-title">Tracks Listened</h3>
        <p class="playlist-desc">Every song you play helps personalize your music recommendations and personality profile.</p>
        <a href="history.php" class="btn btn-secondary btn-sm" style="width: fit-content;">View Listening History</a>
    </div>

    <div class="card" style="padding: 24px;">
        <div class="playlist-card-top">
            <div class="playlist-icon"><i class="fa-solid fa-compact-disc"></i></div>
            <span style="font-size: 1.8rem; font-weight: 800; color: var(--forest-200);"><?= $playlistCount ?></span>
        </div>
        <h3 class="playlist-title">Personal Playlists</h3>
        <p class="playlist-desc">Create custom collections, organize by vibe, and listen on repeat.</p>
        <a href="playlists.php" class="btn btn-secondary btn-sm" style="width: fit-content;">Manage Playlists</a>
    </div>
</div>

<div class="page-header">
    <h2 style="font-size: 1.3rem; color: #fff; margin: 0;">Explore Features</h2>
</div>

<div class="grid-cards">
    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-compass"></i></div>
            </div>
            <h3 class="playlist-title">Music Discovery</h3>
            <p class="playlist-desc">Browse the catalog, search by artist or track, and stream directly via YouTube preview.</p>
        </div>
        <div class="playlist-actions">
            <a href="index.php" class="btn btn-primary btn-sm">Browse Catalog</a>
        </div>
    </div>

    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-sparkles"></i></div>
            </div>
            <h3 class="playlist-title">Recommendations</h3>
            <p class="playlist-desc">Smart song suggestions tailored to your top genres and recent listening activity.</p>
        </div>
        <div class="playlist-actions">
            <a href="recommendations.php" class="btn btn-primary btn-sm">Get Suggestions</a>
        </div>
    </div>

    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-crown"></i></div>
            </div>
            <h3 class="playlist-title">Premium Suite</h3>
            <p class="playlist-desc">Top artists rankings, entertainment personality analysis, weekly movie pairings, and offline downloads.</p>
        </div>
        <div class="playlist-actions">
            <a href="<?= $subData ? 'premium_dashboard.php' : 'subscribe.php' ?>" class="btn btn-primary btn-sm">
                <?= $subData ? 'Open Premium Hub' : 'View Plan (₹99)' ?>
            </a>
        </div>
    </div>

    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-user-gear"></i></div>
            </div>
            <h3 class="playlist-title">Profile &amp; Settings</h3>
            <p class="playlist-desc">Manage account information, update email or password, and view login activity.</p>
        </div>
        <div class="playlist-actions">
            <a href="profile.php" class="btn btn-secondary btn-sm">Account Profile</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
