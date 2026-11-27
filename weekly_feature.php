<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

require_once __DIR__ . '/fetch_weekly_movie.php';
require_once __DIR__ . '/fetch_character.php';
require_once __DIR__ . '/fetch_personality.php';

$user_id = (int)$_SESSION['user_id'];
$top_genre = fetchUserTopGenre($conn, $user_id);
$personalityData = fetchUserPersonality($conn, $user_id);
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
        <h1 class="page-title">WEEKLY CINEMA &amp; CHARACTER</h1>
        <p class="page-subtitle">Curated weekly cinema and character pairing matched directly to your music listening profile.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="character_recommendation.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-user-astronaut"></i> Character Focus
        </a>
        <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Premium Hub
        </a>
    </div>
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
                <i class="fa-solid fa-calendar-week"></i> Active Week: <?= htmlspecialchars($week_start) ?> to <?= htmlspecialchars($week_end) ?> &bull; <?= (int)$top_genre['play_count'] ?> streams in <?= htmlspecialchars($top_genre['genre_name']) ?>
            </div>
            <h2 class="insight-title">Based on your top genre: <?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></h2>
            <p class="insight-text">
                Matched to your music personality: 
                <strong><?= htmlspecialchars($personalityData['personality']['personality_title'] ?? 'The Melodic Muse') ?></strong>.
            </p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></div>
    </div>

    <div class="two-col-grid" style="align-items: stretch;">
        <!-- 1. WEEKLY MOVIE CARD -->
        <div class="feature-card-rich">
            <div class="feature-card-header">
                <span class="premium-badge"><i class="fa-solid fa-clapperboard"></i> This Week's Movie</span>
                <span class="status-pill status-active"><i class="fa-solid fa-ticket"></i> Curated Film</span>
            </div>

            <?php if ($movie !== null): ?>
                <div class="feature-media-box">
                    <img src="<?= htmlspecialchars($movie['movie_poster_url']) ?>" 
                         alt="<?= htmlspecialchars($movie['movie_name']) ?> Poster" 
                         class="feature-poster-img"
                         onerror="this.onerror=null; this.src='assets/images/placeholder_movie.svg';">
                    <div class="feature-poster-badges">
                        <span class="badge-year"><?= (int)$movie['movie_year'] ?></span>
                        <span class="badge-genre"><?= htmlspecialchars($movie['genre_name'] ?? $top_genre['genre_name']) ?></span>
                    </div>
                </div>

                <div class="feature-content-body">
                    <h2 class="feature-main-title"><?= htmlspecialchars($movie['movie_name']) ?></h2>
                    <div class="feature-meta-line">
                        <span><i class="fa-solid fa-calendar"></i> Released: <?= (int)$movie['movie_year'] ?></span> &bull; 
                        <span><i class="fa-solid fa-guitar"></i> Vibe: <?= htmlspecialchars($movie['genre_name'] ?? $top_genre['genre_name']) ?></span>
                    </div>

                    <div class="feature-synopsis">
                        <h4 class="sub-label"><i class="fa-solid fa-align-left"></i> Synopsis</h4>
                        <p><?= nl2br(htmlspecialchars($movie['movie_description'])) ?></p>
                    </div>

                    <div class="feature-highlight-box movie-highlight">
                        <h4><i class="fa-solid fa-headphones"></i> Why This Movie Fits Your Music Taste</h4>
                        <p><?= htmlspecialchars($movie['movie_music_fit']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); padding: 30px;">No movie details available for this week.</p>
            <?php endif; ?>
        </div>

        <!-- 2. CHARACTER OF THE WEEK CARD -->
        <div class="feature-card-rich">
            <div class="feature-card-header">
                <span class="premium-badge" style="background: rgba(33, 55, 34, 0.45); color: var(--forest-200); border-color: var(--border-forest);">
                    <i class="fa-solid fa-user-astronaut"></i> Character of the Week
                </span>
                <span class="status-pill" style="background: rgba(44, 38, 63, 0.65); color: var(--plum-100); border: 1px solid var(--border-subtle);">
                    <i class="fa-solid fa-sparkles"></i> Music Twin
                </span>
            </div>

            <?php if ($character !== null): ?>
                <div class="feature-media-box">
                    <img src="<?= htmlspecialchars($character['character_image_url']) ?>" 
                         alt="Character portrait of <?= htmlspecialchars($character['character_name']) ?> from <?= htmlspecialchars($character['character_show']) ?>" 
                         class="feature-poster-img"
                         onerror="this.onerror=null; this.src='assets/images/placeholder_character.svg'; this.alt='Character portrait unavailable for <?= addslashes(htmlspecialchars($character['character_name'])) ?>';">
                    <div class="feature-poster-badges">
                        <span class="badge-origin"><i class="fa-solid fa-film"></i> <?= htmlspecialchars($character['character_show']) ?></span>
                    </div>
                </div>

                <div class="feature-content-body">
                    <h2 class="feature-main-title"><?= htmlspecialchars($character['character_name']) ?></h2>
                    <div class="feature-meta-line">
                        <span><i class="fa-solid fa-tv"></i> From: <strong><?= htmlspecialchars($character['character_show']) ?></strong></span>
                    </div>

                    <div class="feature-synopsis">
                        <h4 class="sub-label"><i class="fa-solid fa-fingerprint"></i> Personality &amp; Core Traits</h4>
                        <p class="traits-preview"><?= htmlspecialchars($character['character_traits']) ?></p>
                        <p><?= nl2br(htmlspecialchars($character['character_description'])) ?></p>
                    </div>

                    <div class="feature-highlight-box character-highlight">
                        <h4><i class="fa-solid fa-wand-magic-sparkles"></i> Why They Match Your Music Personality</h4>
                        <p><?= htmlspecialchars($character['character_music_match']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); padding: 30px;">No character details available for this week.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
