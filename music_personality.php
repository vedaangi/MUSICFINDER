<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

require_once __DIR__ . '/fetch_personality.php';
$user_id = (int)$_SESSION['user_id'];
$data = fetchUserPersonality($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">YOUR MUSIC PERSONALITY</h1>
        <p class="page-subtitle">A music-themed profile matched deterministically to your most-listened genre.</p>
    </div>
    <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Premium Hub
    </a>
</div>

<?php if (!$data['has_history']): ?>
    <div class="empty-state">
        <i class="fa-solid fa-music"></i>
        <h3>No Listening History Available</h3>
        <p>Listen to a few tracks first so we can identify your top genre and show your matching music personality.</p>
        <a href="index.php" class="btn btn-primary">Discover Music</a>
    </div>
<?php elseif ($data['personality'] === null): ?>
    <div class="personality-card">
        <span class="genre-tag">Your Top Genre: <?= htmlspecialchars(strtoupper($data['top_genre']['genre_name'])) ?></span>
        <h2 class="personality-title">Personality Profile Coming Soon</h2>
        <p style="color: var(--text-secondary); line-height: 1.6;">
            Your most-played genre is <strong><?= htmlspecialchars($data['top_genre']['genre_name']) ?></strong>
            (<?= (int)$data['top_genre']['play_count'] ?> plays), but no predefined personality description has been configured for this genre yet.
        </p>
        <div class="disclaimer-note">
            <i class="fa-solid fa-circle-info"></i>
            <span>Entertainment Notice: Your Music Personality is a lighthearted entertainment feature based on your listening history, not a psychological assessment.</span>
        </div>
    </div>
<?php else: ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-masks-theater"></i> Genre Match
            </div>
            <h2 class="insight-title">Your Top Genre: <?= htmlspecialchars(strtoupper($data['top_genre']['genre_name'])) ?></h2>
            <p class="insight-text">
                Determined from <?= (int)$data['top_genre']['play_count'] ?> plays in your listening history.
            </p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($data['top_genre']['genre_name'])) ?></div>
    </div>

    <div class="personality-card">
        <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--accent); font-weight: 700;">
            Your Personality:
        </div>
        <h2 class="personality-title"><?= htmlspecialchars($data['personality']['personality_title']) ?></h2>
        <p style="color: var(--text-primary); font-size: 1rem; line-height: 1.65;">
            <?= htmlspecialchars($data['personality']['personality_description']) ?>
        </p>

        <div class="disclaimer-note">
            <i class="fa-solid fa-circle-info"></i>
            <span>Entertainment Notice: This is an entertainment feature based on your listening history, not a psychological assessment.</span>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
