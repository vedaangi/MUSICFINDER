<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session_helper.php';

$user_id = getCurrentUserId();

// Fetch listening history
$sql = "SELECT lh.id AS history_id, lh.listened_at, s.id AS song_id, s.title, s.artist, s.genre, s.youtube_url
        FROM listening_history lh
        JOIN songs s ON lh.song_id = s.id
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
        <h1 class="page-title">Listening History</h1>
        <p class="page-subtitle">Tracks you have played on MusicFinder. Used to generate your personal recommendations.</p>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h3>No listening history yet</h3>
        <p>Songs you play will show up here, helping MusicFinder learn your music preferences.</p>
        <a href="index.php" class="btn btn-primary">
            <i class="fa-solid fa-play"></i> Discover & Play Songs
        </a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($history as $item): ?>
            <div class="song-card">
                <div class="song-info">
                    <div class="song-play-icon" onclick="playSong(<?= $item['song_id'] ?>, '<?= addslashes(htmlspecialchars($item['title'])) ?>', '<?= addslashes(htmlspecialchars($item['artist'])) ?>', '<?= htmlspecialchars($item['youtube_url']) ?>')">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($item['title']) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($item['artist']) ?></span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="genre-tag"><?= htmlspecialchars($item['genre']) ?></span>
                            <span style="font-size: 0.78rem; color: #6b7280;">Played <?= date('M d, Y h:i A', strtotime($item['listened_at'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="song-actions">
                    <?php if (!empty($item['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($item['youtube_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Watch on YouTube">
                            <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> YouTube
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $item['song_id'] ?>, '<?= addslashes(htmlspecialchars($item['title'])) ?>')">
                        <i class="fa-solid fa-plus"></i> Add to Playlist
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
