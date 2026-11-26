<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session_helper.php';

$user_id = getCurrentUserId();

// Fetch summary metrics
$playlist_count = 0;
$res = $conn->query("SELECT COUNT(*) AS c FROM playlists WHERE user_id = $user_id");
if ($res && $row = $res->fetch_assoc()) {
    $playlist_count = $row['c'];
}

$history_count = 0;
$res = $conn->query("SELECT COUNT(*) AS c FROM listening_history WHERE user_id = $user_id");
if ($res && $row = $res->fetch_assoc()) {
    $history_count = $row['c'];
}

// Top genre
$top_genre = 'None';
$res = $conn->query("SELECT s.genre, COUNT(lh.id) AS cnt 
                     FROM listening_history lh 
                     JOIN songs s ON lh.song_id = s.id 
                     WHERE lh.user_id = $user_id 
                     GROUP BY s.genre 
                     ORDER BY cnt DESC LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $top_genre = $row['genre'];
}

// Fetch some sample songs for quick discovery
$songs = [];
$res = $conn->query("SELECT * FROM songs ORDER BY id ASC LIMIT 8");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $songs[] = $row;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome to MusicFinder</h1>
        <p class="page-subtitle">Your personalized hub for music discovery and custom playlists.</p>
    </div>
</div>

<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 32px;">
    <div class="card">
        <span style="font-size: 0.85rem; color: #9ca3af;">My Playlists</span>
        <h2 style="font-size: 1.8rem; margin: 8px 0; color: #fff;"><?= $playlist_count ?></h2>
        <a href="playlists.php" class="btn btn-secondary btn-sm" style="width: fit-content;">Manage Playlists</a>
    </div>

    <div class="card">
        <span style="font-size: 0.85rem; color: #9ca3af;">Top Listened Genre</span>
        <h2 style="font-size: 1.8rem; margin: 8px 0; color: #1db954;"><?= htmlspecialchars($top_genre) ?></h2>
        <a href="recommendations.php" class="btn btn-secondary btn-sm" style="width: fit-content;">View Recommendations</a>
    </div>

    <div class="card">
        <span style="font-size: 0.85rem; color: #9ca3af;">Songs Played</span>
        <h2 style="font-size: 1.8rem; margin: 8px 0; color: #fff;"><?= $history_count ?></h2>
        <a href="history.php" class="btn btn-secondary btn-sm" style="width: fit-content;">View History</a>
    </div>
</div>

<div class="page-header">
    <div>
        <h2 style="font-size: 1.3rem; color: #fff;">Featured Catalog Songs</h2>
        <p class="page-subtitle">Play songs to build your listening history and get personalized recommendations.</p>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 12px;">
    <?php foreach ($songs as $song): ?>
        <div class="song-card">
            <div class="song-info">
                <div class="song-play-icon" onclick="playSong(<?= $song['id'] ?>, '<?= addslashes(htmlspecialchars($song['title'])) ?>', '<?= addslashes(htmlspecialchars($song['artist'])) ?>', '<?= htmlspecialchars($song['youtube_url']) ?>')">
                    <i class="fa-solid fa-play"></i>
                </div>
                <div class="song-details">
                    <span class="song-title"><?= htmlspecialchars($song['title']) ?></span>
                    <span class="song-artist"><?= htmlspecialchars($song['artist']) ?></span>
                    <span class="genre-tag"><?= htmlspecialchars($song['genre']) ?></span>
                </div>
            </div>
            <div class="song-actions">
                <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $song['id'] ?>, '<?= addslashes(htmlspecialchars($song['title'])) ?>')">
                    <i class="fa-solid fa-plus"></i> Add to Playlist
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
