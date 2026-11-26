<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

$user_id = getCurrentUserId();

$rec_data = getRecommendedSongsList($conn, $user_id, 8);
$top_genre = $rec_data['top_genre'];
$top_count = $rec_data['top_count'];
$genre_stats = $rec_data['breakdown'];
$is_personalized = $rec_data['is_personalized'];
$recommendations = $rec_data['recommendations'];
$user_fav_ids = getUserFavoriteSongIds($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Personalized Recommendations</h1>
        <p class="page-subtitle">Smart music suggestions derived from your listening history and preferences.</p>
    </div>
</div>

<?php if ($is_personalized): ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-fire"></i> Listening Affinity Detected
            </div>
            <h2 class="insight-title">Your Top Genre: <?= htmlspecialchars($top_genre) ?></h2>
            <p class="insight-text">
                Based on <?= $top_count ?> plays in your history, here are fresh <?= htmlspecialchars($top_genre) ?> tracks not yet in any of your playlists:
            </p>
            <div style="display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap;">
                <?php foreach ($genre_stats as $stat): ?>
                    <span style="font-size: 0.78rem; padding: 4px 12px; background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 16px; color: var(--accent-dark); font-weight: 600;">
                        <i class="fa-solid fa-headphones" style="font-size: 0.7rem; color: var(--accent);"></i> <?= htmlspecialchars($stat['genre']) ?>: <?= $stat['play_count'] ?> plays
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="insight-badge">
            <?= htmlspecialchars($top_genre) ?>
        </div>
    </div>
<?php else: ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Popular Suggestions
            </div>
            <h2 class="insight-title">Discover Something New</h2>
            <p class="insight-text">
                Play more tracks in the catalog to build your personalized genre profile! Here are popular songs to get you started.
            </p>
        </div>
        <a href="catalog.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-music"></i> Browse Catalog
        </a>
    </div>
<?php endif; ?>

<div class="page-header" style="margin-top: 24px;">
    <h2 style="font-size: 1.3rem; color: var(--text-primary);">
        <?= $is_personalized ? 'Recommended ' . htmlspecialchars($top_genre) . ' Tracks' : 'Popular Songs for You' ?>
    </h2>
</div>

<?php if (empty($recommendations)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-check-circle" style="color: var(--accent);"></i>
        <h3>You are all caught up!</h3>
        <p>You have already added all recommended songs to your playlists. Explore the catalog to find more music.</p>
        <a href="catalog.php" class="btn btn-primary">Browse Catalog</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($recommendations as $song): 
            $s_id = (int)$song['song_id'];
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];
            $is_fav = in_array($s_id, $user_fav_ids);
        ?>
            <div class="song-card">
                <div class="song-info">
                    <div class="song-play-icon" onclick="playSong(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= addslashes(htmlspecialchars($s_artist)) ?>', '<?= htmlspecialchars($song['youtube_url'] ?? '') ?>')">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($s_title) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($s_artist) ?></span>
                        <span class="genre-tag"><?= htmlspecialchars($s_genre) ?></span>
                    </div>
                </div>
                <div class="song-actions">
                    <button class="btn btn-secondary btn-sm fav-toggle-btn fav-btn-<?= $s_id ?> <?= $is_fav ? 'active' : '' ?>" onclick="toggleFavorite(<?= $s_id ?>, this)" title="<?= $is_fav ? 'Unlike' : 'Like' ?>">
                        <i class="<?= $is_fav ? 'fa-solid' : 'fa-regular' ?> fa-heart" style="<?= $is_fav ? 'color: var(--accent);' : '' ?>"></i>
                    </button>
                    <?php if (!empty($song['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($song['youtube_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Watch on YouTube">
                            <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> <span class="hide-mobile">YouTube</span>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                        <i class="fa-solid fa-plus"></i> <span class="hide-mobile">Playlist</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
