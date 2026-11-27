<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();

$sql = "SELECT lh.id AS history_id, lh.listened_at, s.id, s.song_id, 
               COALESCE(s.song_name, s.title) as song_name, 
               COALESCE(a.artist_name, s.artist, 'Unknown Artist') as artist_name, 
               COALESCE(g.genre_name, s.genre, 'General') as genre_name, 
               s.youtube_url, s.preview_url, s.album_art
        FROM listening_history lh
        JOIN songs s ON (lh.song_id = s.song_id OR lh.song_id = s.id)
        LEFT JOIN artists a ON s.artist_id = a.artist_id
        LEFT JOIN genres g ON s.genre_id = g.genre_id
        WHERE lh.user_id = ?
        ORDER BY lh.listened_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Listening History</h1>
        <p class="page-subtitle">Timeline of tracks you have streamed on VARP. Feeds your recommendations and music personality.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-compass"></i> Discover More Songs
        </a>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h3>No listening history yet</h3>
        <p>Start streaming songs in Explore or Playlists to build your listening history and unlock personalized music insights in VARP!</p>
        <a href="index.php" class="btn btn-primary">
            <i class="fa-solid fa-play"></i> Discover Songs
        </a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($history as $item): 
            $s_id = (int)($item['song_id'] ?: $item['id']);
            $s_title = $item['song_name'];
            $s_artist = $item['artist_name'];
            $s_genre = $item['genre_name'];

            $has_direct_yt = !empty($item['youtube_url']) && (str_contains($item['youtube_url'], 'youtube.com/watch') || str_contains($item['youtube_url'], 'youtu.be/'));
            $yt_link = !empty($item['youtube_url']) ? $item['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($s_title . ' ' . $s_artist . ' audio'));
            $yt_tooltip = $has_direct_yt ? ('Watch "' . htmlspecialchars($s_title) . '" on YouTube') : ('Find "' . htmlspecialchars($s_title) . '" on YouTube');
        ?>
            <div class="song-card-row">
                <div class="song-info">
                    <div class="song-play-icon" style="cursor: pointer;" onclick="playTrackVideo('<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= htmlspecialchars($item['youtube_url'] ?? '') ?>', <?= $s_id ?>)" title="Play Track">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($s_title) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($s_artist) ?></span>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                            <span class="genre-tag"><?= htmlspecialchars($s_genre) ?></span>
                            <span style="font-size: 0.78rem; color: var(--text-muted);">
                                <i class="fa-regular fa-clock"></i> <?= date('M d, Y h:i A', strtotime($item['listened_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <a href="<?= htmlspecialchars($yt_link) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="btn-youtube-icon" 
                       title="<?= $yt_tooltip ?>" 
                       aria-label="<?= $yt_tooltip ?>">
                        <i class="fa-brands fa-youtube"></i> <?= $has_direct_yt ? 'YouTube' : 'Find on YouTube' ?>
                    </a>

                    <?php if ($has_direct_yt): ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="playTrackVideo('<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= htmlspecialchars($item['youtube_url']) ?>', <?= $s_id ?>)" title="Open player">
                            <i class="fa-solid fa-play"></i> Player
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
