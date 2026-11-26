<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

$user_id = getCurrentUserId();

$history = getUserHistoryList($conn, $user_id, 100);
$summary = getListeningSummaryStats($conn, $user_id);
$genre_info = getUserTopGenreInfo($conn, $user_id);
$user_fav_ids = getUserFavoriteSongIds($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Listening History</h1>
        <p class="page-subtitle">A real-time record of tracks you've streamed. Used to train your personalized recommendation engine.</p>
    </div>
</div>

<!-- Listening Analytics Summary Banner -->
<?php if (!empty($history)): ?>
    <div class="card" style="margin-bottom: 24px; padding: 22px; background: linear-gradient(135deg, #ffffff 0%, #fdf2f8 100%);">
        <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 14px;">
            <i class="fa-solid fa-chart-pie" style="color: var(--accent);"></i> Listening Activity Analytics
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
            <div style="padding: 12px 16px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius);">
                <span style="font-size: 0.8rem; color: var(--text-muted);">Total Streams</span>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-primary); margin-top: 2px;">
                    <?= $summary['total_plays'] ?>
                </div>
            </div>

            <div style="padding: 12px 16px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius);">
                <span style="font-size: 0.8rem; color: var(--text-muted);">Unique Songs Explored</span>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--accent); margin-top: 2px;">
                    <?= $summary['unique_songs'] ?>
                </div>
            </div>

            <div style="padding: 12px 16px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius);">
                <span style="font-size: 0.8rem; color: var(--text-muted);">Top Genre</span>
                <div style="font-size: 1.25rem; font-weight: 700; color: var(--accent-dark); margin-top: 4px;">
                    <?= htmlspecialchars($genre_info['top_genre'] ?? 'None') ?>
                </div>
            </div>

            <?php if (!empty($summary['most_played'])): ?>
                <div style="padding: 12px 16px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius);">
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Most Streamed Track</span>
                    <div style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($summary['most_played']['song_name']) ?>
                    </div>
                    <span style="font-size: 0.78rem; color: var(--text-muted);">
                        <?= $summary['most_played']['play_count'] ?> plays &bull; <?= htmlspecialchars($summary['most_played']['artist_name']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($history)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h3>No listening history yet</h3>
        <p>Songs you play will automatically appear here, helping MusicFinder learn your music preferences.</p>
        <a href="catalog.php" class="btn btn-primary">
            <i class="fa-solid fa-play"></i> Discover & Play Songs
        </a>
    </div>
<?php else: ?>
    <!-- Live Search within History -->
    <div style="margin-bottom: 16px; position: relative; max-width: 360px;">
        <input type="text" id="history-filter" class="form-control" placeholder="Search played songs or artists..." oninput="filterHistoryLive()" style="padding-left: 38px; width: 100%;">
        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
    </div>

    <div style="display: flex; flex-direction: column; gap: 12px;" id="history-container">
        <?php foreach ($history as $item): 
            $s_id = (int)$item['song_id'];
            $s_title = $item['song_name'];
            $s_artist = $item['artist_name'];
            $s_genre = $item['genre_name'];
            $is_fav = in_array($s_id, $user_fav_ids);
        ?>
            <div class="song-card history-row-item" data-title="<?= strtolower(htmlspecialchars($s_title)) ?>" data-artist="<?= strtolower(htmlspecialchars($s_artist)) ?>">
                <div class="song-info">
                    <div class="song-play-icon" onclick="playSong(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= addslashes(htmlspecialchars($s_artist)) ?>', '<?= htmlspecialchars($item['youtube_url'] ?? '') ?>')">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($s_title) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($s_artist) ?></span>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                            <span class="genre-tag"><?= htmlspecialchars($s_genre) ?></span>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">
                                <i class="fa-regular fa-clock"></i> Played <?= date('M d, Y \a\t h:i A', strtotime($item['listened_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="song-actions">
                    <button class="btn btn-secondary btn-sm fav-toggle-btn fav-btn-<?= $s_id ?> <?= $is_fav ? 'active' : '' ?>" onclick="toggleFavorite(<?= $s_id ?>, this)" title="<?= $is_fav ? 'Unlike' : 'Like' ?>">
                        <i class="<?= $is_fav ? 'fa-solid' : 'fa-regular' ?> fa-heart" style="<?= $is_fav ? 'color: var(--accent);' : '' ?>"></i>
                    </button>
                    <?php if (!empty($item['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($item['youtube_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Watch on YouTube">
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
function filterHistoryLive() {
    const q = document.getElementById('history-filter').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.history-row-item');
    rows.forEach(row => {
        const title = row.getAttribute('data-title') || '';
        const artist = row.getAttribute('data-artist') || '';
        if (title.includes(q) || artist.includes(q)) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
