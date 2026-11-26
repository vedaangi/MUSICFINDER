<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

require_once __DIR__ . '/fetch_weekly_movie.php';
require_once __DIR__ . '/fetch_character.php';

$user_id = (int)$_SESSION['user_id'];
$top_genre = fetchUserTopGenre($conn, $user_id);
$movie = null;
$character = null;

if ($top_genre !== null) {
    $movie = fetchWeeklyMovie($conn, $top_genre['genre_id']);
    $character = fetchWeeklyCharacter($conn, $top_genre['genre_id']);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">WEEKLY FEATURE</h1>
        <p class="page-subtitle">Your music personality this week</p>
    </div>
    <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Premium Hub
    </a>
</div>

<?php if ($top_genre === null): ?>
    <div class="empty-state">
        <i class="fa-solid fa-film"></i>
        <h3>No Listening History Found</h3>
        <p>Play some songs first so we can determine your top genre and match this week's movie and character.</p>
        <a href="index.php" class="btn btn-primary">Explore Music</a>
    </div>
<?php elseif ($movie === null && $character === null): ?>
    <div class="insight-banner">
        <div>
            <h2 class="insight-title">Based on your top genre: <?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></h2>
            <p class="insight-text">Your most-listened genre this week is <?= htmlspecialchars($top_genre['genre_name']) ?>.</p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></div>
    </div>

    <div class="empty-state">
        <i class="fa-solid fa-calendar-xmark"></i>
        <h3>No Weekly Feature Published Yet</h3>
        <p>No active movie or character recommendation has been scheduled for <?= htmlspecialchars($top_genre['genre_name']) ?> for the current week (<?= htmlspecialchars(date('Y-m-d')) ?>).</p>
    </div>
<?php else:
    $week_start = $movie['week_start'] ?? $character['week_start'];
    $week_end = $movie['week_end'] ?? $character['week_end'];
?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-calendar-week"></i> Active Week: <?= htmlspecialchars($week_start) ?> to <?= htmlspecialchars($week_end) ?>
            </div>
            <h2 class="insight-title">Based on your top genre: <?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></h2>
            <p class="insight-text">Consistent weekly movie and character pairing for <?= htmlspecialchars($top_genre['genre_name']) ?> listeners.</p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></div>
    </div>

    <div class="two-col-grid">
        <div class="feature-card">
            <span class="premium-badge"><i class="fa-solid fa-clapperboard"></i> This Week's Movie</span>
            <?php if ($movie !== null): ?>
                <h2 style="font-size: 1.45rem; color: #fff; margin-bottom: 10px;"><?= htmlspecialchars($movie['movie_name']) ?></h2>
                <p style="color: var(--text-secondary); line-height: 1.65;"><?= nl2br(htmlspecialchars($movie['movie_description'])) ?></p>
            <?php else: ?>
                <p style="color: var(--text-muted);">No movie details available for this week.</p>
            <?php endif; ?>
        </div>

        <div class="feature-card">
            <span class="premium-badge"><i class="fa-solid fa-user-astronaut"></i> Character of the Week</span>
            <?php if ($character !== null): ?>
                <h2 style="font-size: 1.45rem; color: #fff; margin-bottom: 10px;"><?= htmlspecialchars($character['character_name']) ?></h2>
                <p style="color: var(--text-secondary); line-height: 1.65;"><?= nl2br(htmlspecialchars($character['character_description'])) ?></p>
            <?php else: ?>
                <p style="color: var(--text-muted);">No character details available for this week.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
