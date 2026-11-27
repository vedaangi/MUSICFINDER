<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

require_once __DIR__ . '/fetch_character.php';
require_once __DIR__ . '/fetch_personality.php';

$user_id = (int)$_SESSION['user_id'];
$top_genre = fetchUserTopGenre($conn, $user_id);
$personalityData = fetchUserPersonality($conn, $user_id);
$character = null;

if ($top_genre !== null) {
    $character = fetchWeeklyCharacter($conn, $top_genre['genre_id']);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">CHARACTER RECOMMENDATIONS</h1>
        <p class="page-subtitle">Your fictional music twin based on your listening habits and music personality</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="weekly_feature.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-clapperboard"></i> Weekly Feature
        </a>
        <a href="music_personality.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-masks-theater"></i> Personality
        </a>
    </div>
</div>

<?php if ($top_genre === null): ?>
    <div class="empty-state">
        <i class="fa-solid fa-user-astronaut"></i>
        <h3>No Listening History Yet</h3>
        <p>Listen to a few tracks across our library or search worldwide music to discover your character recommendation!</p>
        <a href="index.php" class="btn btn-primary">Discover Music</a>
    </div>
<?php elseif ($character === null): ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-compact-disc"></i> Top Streamed Genre: <?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?>
            </div>
            <h2 class="insight-title">Character Pairing in Progress</h2>
            <p class="insight-text">No active character has been scheduled for <?= htmlspecialchars($top_genre['genre_name']) ?> this week.</p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></div>
    </div>
    <div class="empty-state">
        <i class="fa-solid fa-calendar-xmark"></i>
        <h3>Check Back Soon!</h3>
        <p>Our music curators update character pairings regularly.</p>
    </div>
<?php else: ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 6px;">
                <i class="fa-solid fa-sparkles"></i> Musical Twin Found &bull; <?= (int)$top_genre['play_count'] ?> Streams in <?= htmlspecialchars($top_genre['genre_name']) ?>
            </div>
            <h2 class="insight-title">Based on your top genre: <?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></h2>
            <p class="insight-text">
                Your listening profile matches the vibe of 
                <strong><?= htmlspecialchars($personalityData['personality']['personality_title'] ?? 'The Sonic Visionary') ?></strong>.
            </p>
        </div>
        <div class="insight-badge"><?= htmlspecialchars(strtoupper($top_genre['genre_name'])) ?></div>
    </div>

    <div class="character-card-detailed">
        <div class="character-image-col">
            <div class="character-poster-wrapper">
                <img src="<?= htmlspecialchars($character['character_image_url']) ?>" 
                     alt="Character portrait of <?= htmlspecialchars($character['character_name']) ?> from <?= htmlspecialchars($character['character_show']) ?>" 
                     class="character-img"
                     onerror="this.onerror=null; this.src='assets/images/placeholder_character.svg'; this.alt='Character portrait unavailable for <?= addslashes(htmlspecialchars($character['character_name'])) ?>';">
                <div class="character-badge-overlay">
                    <i class="fa-solid fa-star"></i> Featured Character
                </div>
            </div>
            <div class="character-origin-tag">
                <i class="fa-solid fa-film"></i> From: <strong><?= htmlspecialchars($character['character_show']) ?></strong>
            </div>
        </div>

        <div class="character-info-col">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <div>
                    <span class="genre-tag"><i class="fa-solid fa-music"></i> <?= htmlspecialchars($top_genre['genre_name']) ?> Muse</span>
                    <h2 class="character-name-title"><?= htmlspecialchars($character['character_name']) ?></h2>
                    <span class="character-subtitle">From <em><?= htmlspecialchars($character['character_show']) ?></em></span>
                </div>
                <div class="personality-match-pill">
                    <i class="fa-solid fa-heart"></i> <?= htmlspecialchars($personalityData['personality']['personality_title'] ?? 'Twin Spirit') ?>
                </div>
            </div>

            <div class="character-section-block">
                <h4 class="section-heading"><i class="fa-solid fa-fingerprint"></i> Personality &amp; Core Traits</h4>
                <p class="traits-text"><?= htmlspecialchars($character['character_traits']) ?></p>
                <p class="bio-text"><?= nl2br(htmlspecialchars($character['character_description'])) ?></p>
            </div>

            <div class="character-section-block match-explanation-block">
                <h4 class="section-heading-highlight">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Why They Match Your Music Personality
                </h4>
                <p class="match-text">
                    <?= htmlspecialchars($character['character_music_match']) ?>
                </p>
            </div>

            <div class="character-actions-row">
                <a href="weekly_feature.php" class="btn btn-primary">
                    <i class="fa-solid fa-clapperboard"></i> See Paired Movie (<?= htmlspecialchars($character['character_show']) ?>)
                </a>
                <a href="recommendations.php" class="btn btn-secondary">
                    <i class="fa-solid fa-headphones"></i> Listen to <?= htmlspecialchars($top_genre['genre_name']) ?> Tracks
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
