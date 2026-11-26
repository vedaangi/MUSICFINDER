<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

$user_id = getCurrentUserId();
$favorites = getUserFavoritesList($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-heart" style="color: var(--accent); margin-right: 8px;"></i> Liked Songs</h1>
        <p class="page-subtitle">Your personal collection of favorite tracks, ready to play or add to custom playlists.</p>
    </div>
    <?php if (!empty($favorites)): ?>
        <button class="btn btn-primary" onclick="playAllFavorites()">
            <i class="fa-solid fa-play"></i> Play All (<?= count($favorites) ?>)
        </button>
    <?php endif; ?>
</div>

<?php if (empty($favorites)): ?>
    <div class="empty-state">
        <i class="fa-regular fa-heart" style="color: var(--accent); font-size: 3rem;"></i>
        <h3>No liked songs yet</h3>
        <p>Songs you like by tapping the heart icon anywhere across MusicFinder will be saved here for quick listening.</p>
        <a href="catalog.php" class="btn btn-primary">
            <i class="fa-solid fa-magnifying-glass"></i> Explore Music Catalog
        </a>
    </div>
<?php else: ?>
    <div style="margin-bottom: 16px; color: var(--text-secondary); font-size: 0.9rem;">
        You have saved <strong style="color: var(--text-primary);"><?= count($favorites) ?></strong> favorite <?= count($favorites) === 1 ? 'track' : 'tracks' ?>:
    </div>

    <div style="display: flex; flex-direction: column; gap: 12px;" id="favorites-list">
        <?php foreach ($favorites as $song): 
            $s_id = (int)$song['song_id'];
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];
        ?>
            <div class="song-card" id="fav-row-<?= $s_id ?>">
                <div class="song-info">
                    <div class="song-play-icon" onclick="playSong(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= addslashes(htmlspecialchars($s_artist)) ?>', '<?= htmlspecialchars($song['youtube_url'] ?? '') ?>')">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($s_title) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($s_artist) ?></span>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                            <span class="genre-tag"><?= htmlspecialchars($s_genre) ?></span>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">
                                Liked <?= date('M d, Y', strtotime($song['favorited_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="song-actions">
                    <button class="btn btn-secondary btn-sm fav-toggle-btn active" onclick="toggleFavorite(<?= $s_id ?>, this)" title="Remove from favorites">
                        <i class="fa-solid fa-heart" style="color: var(--accent);"></i> <span class="hide-mobile">Liked</span>
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

<script>
function playAllFavorites() {
    const firstPlayBtn = document.querySelector('.song-play-icon');
    if (firstPlayBtn) firstPlayBtn.click();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
