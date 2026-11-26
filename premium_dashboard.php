<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';

try {
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $username = $row['username'];
            $email = $row['email'];
        }
    }
} catch (Throwable $ignored) {
}

$downloadable_count = 0;
try {
    $res = $conn->query("SELECT COUNT(*) AS total FROM songs WHERE audio_file IS NOT NULL AND audio_file <> ''");
    if ($res && $row = $res->fetch_assoc()) {
        $downloadable_count = (int)$row['total'];
    }
} catch (Throwable $ignored) {
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, <?= htmlspecialchars($username) ?>!</h1>
        <p class="page-subtitle">
            <?= $email !== '' ? 'Signed in as ' . htmlspecialchars($email) . ' — ' : '' ?>Your Music Finder Premium workspace.
        </p>
    </div>
    <span class="status-pill status-active">
        <i class="fa-solid fa-crown"></i> Premium Active
    </span>
</div>

<div class="insight-banner">
    <div>
        <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 6px;">
            Subscription Details
        </div>
        <h2 class="insight-title"><?= htmlspecialchars($subscription['plan']) ?></h2>
        <p class="insight-text">
            Status: <strong>Active</strong> |
            Started: <?= htmlspecialchars($subscription['start_date']) ?> |
            Expires on: <strong><?= htmlspecialchars($subscription['end_date']) ?></strong>
        </p>
    </div>
    <a href="subscribe.php" class="btn btn-secondary btn-sm">View Plan</a>
</div>

<div class="grid-cards">
    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-ranking-star"></i></div>
            </div>
            <h3 class="playlist-title">Top Artists</h3>
            <p class="playlist-desc">See your top 5 most-played artists ranked directly from your listening history.</p>
        </div>
        <div class="playlist-actions">
            <a href="top_artists.php" class="btn btn-primary btn-sm">View Top Artists</a>
        </div>
    </div>

    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-masks-theater"></i></div>
            </div>
            <h3 class="playlist-title">Music Personality</h3>
            <p class="playlist-desc">Explore the personality profile matched to your most-listened genre.</p>
        </div>
        <div class="playlist-actions">
            <a href="music_personality.php" class="btn btn-primary btn-sm">View Personality</a>
        </div>
    </div>

    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-film"></i></div>
            </div>
            <h3 class="playlist-title">Weekly Movie &amp; Character</h3>
            <p class="playlist-desc">Discover this week's curated movie and character pairing for your top genre.</p>
        </div>
        <div class="playlist-actions">
            <a href="weekly_feature.php" class="btn btn-primary btn-sm">Open Weekly Feature</a>
        </div>
    </div>

    <div class="card">
        <div>
            <div class="playlist-card-top">
                <div class="playlist-icon"><i class="fa-solid fa-download"></i></div>
            </div>
            <h3 class="playlist-title">Eligible Downloads</h3>
            <p class="playlist-desc">
                <?= $downloadable_count > 0
                    ? $downloadable_count . ' permitted audio track(s) ready for offline download.'
                    : 'Browse tracks eligible for offline download once permitted audio files are added.' ?>
            </p>
        </div>
        <div class="playlist-actions">
            <a href="download.php" class="btn btn-primary btn-sm">Go to Downloads</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
