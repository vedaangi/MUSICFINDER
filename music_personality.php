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
        <h1 class="page-title">MUSIC PERSONALITY ARCHIVE</h1>
        <p class="page-subtitle">An editorial archetype matched deterministically to your most-streamed musical genres.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="character_recommendation.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-user-astronaut"></i> Character Muse
        </a>
        <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Premium Hub
        </a>
    </div>
</div>

<?php if (!$data['has_history']): ?>
    <div class="empty-state">
        <i class="fa-solid fa-music"></i>
        <h3>No Listening History Available</h3>
        <p>Stream a few tracks first so we can identify your top genre and reveal your matching music personality.</p>
        <a href="index.php" class="btn btn-primary">Discover Music</a>
    </div>
<?php elseif ($data['personality'] === null): ?>
    <div class="personality-card">
        <span class="genre-tag">Top Genre // <?= htmlspecialchars(strtoupper($data['top_genre']['genre_name'])) ?></span>
        <h2 class="personality-title">Personality Profile Coming Soon</h2>
        <p style="color: var(--text-secondary); line-height: 1.65; max-width: 600px; margin: 0 auto;">
            Your most-played genre is <strong><?= htmlspecialchars($data['top_genre']['genre_name']) ?></strong>
            (<?= (int)$data['top_genre']['play_count'] ?> plays). Our music editors are crafting an archetype for this genre.
        </p>
        <div class="disclaimer-note">
            <i class="fa-solid fa-circle-info"></i>
            <span>Entertainment Notice: Your Music Personality is an artistic entertainment profile based on your listening history, not a psychological evaluation.</span>
        </div>
    </div>
<?php else: ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--forest-100); font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-compass"></i> Musical Identity Match
            </div>
            <h2 class="insight-title">Primary Vibe: <?= htmlspecialchars(strtoupper($data['top_genre']['genre_name'])) ?></h2>
            <p class="insight-text">
                Derived directly from <?= (int)$data['top_genre']['play_count'] ?> streams in your listening history.
            </p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($data['top_genre']['genre_name'])) ?></div>
    </div>

    <div class="personality-card">
        <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--forest-200); font-weight: 700; margin-bottom: 6px;">
            // ARCHETYPE CLASSIFICATION //
        </div>
        <h2 class="personality-title">"<?= htmlspecialchars($data['personality']['personality_title']) ?>"</h2>
        <p style="color: var(--text-primary); font-size: 1.05rem; line-height: 1.75; max-width: 680px; margin: 0 auto 28px auto;">
            <?= htmlspecialchars($data['personality']['personality_description']) ?>
        </p>

        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <a href="character_recommendation.php" class="btn btn-primary">
                <i class="fa-solid fa-user-astronaut"></i> Meet Your Character Muse
            </a>
            <a href="weekly_feature.php" class="btn btn-secondary">
                <i class="fa-solid fa-film"></i> Explore Curated Cinema
            </a>
        </div>

        <div class="disclaimer-note">
            <i class="fa-solid fa-circle-info"></i>
            <span>Entertainment Notice: This is an editorial feature derived from your streaming history.</span>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
