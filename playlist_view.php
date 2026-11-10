<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session_helper.php';

$user_id = getCurrentUserId();
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($playlist_id <= 0) {
    header('Location: playlists.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM playlists WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $playlist_id, $user_id);
$stmt->execute();
$playlist_res = $stmt->get_result();

if ($playlist_res->num_rows === 0) {
    die("Playlist not found or you do not have permission to view it.");
}

$playlist = $playlist_res->fetch_assoc();
$stmt->close();

$songs_sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url, ps.added_at 
              FROM playlist_songs ps
              JOIN songs s ON ps.song_id = s.song_id
              LEFT JOIN artists a ON s.artist_id = a.artist_id
              LEFT JOIN genres g ON s.genre_id = g.genre_id
              WHERE ps.playlist_id = ?
              ORDER BY ps.added_at DESC";
$songs_stmt = $conn->prepare($songs_sql);
$songs_stmt->bind_param("i", $playlist_id);
$songs_stmt->execute();
$songs_res = $songs_stmt->get_result();

$songs = [];
while ($row = $songs_res->fetch_assoc()) {
    $songs[] = $row;
}
$songs_stmt->close();

$avail_sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url 
              FROM songs s 
              LEFT JOIN artists a ON s.artist_id = a.artist_id 
              LEFT JOIN genres g ON s.genre_id = g.genre_id 
              WHERE s.song_id NOT IN (SELECT song_id FROM playlist_songs WHERE playlist_id = ?)
              ORDER BY s.song_name ASC";
$avail_stmt = $conn->prepare($avail_sql);
$avail_stmt->bind_param("i", $playlist_id);
$avail_stmt->execute();
$avail_res = $avail_stmt->get_result();

$available_songs = [];
while ($row = $avail_res->fetch_assoc()) {
    $available_songs[] = $row;
}
$avail_stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="playlists.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to My Playlists
    </a>
</div>

<div class="insight-banner" style="background: linear-gradient(135deg, #1b2838 0%, #15222e 100%); border-color: #2b394e;">
    <div>
        <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af;">Playlist</span>
        <h1 style="font-size: 2rem; color: #fff; margin: 6px 0;" id="view-pl-title"><?= htmlspecialchars($playlist['name']) ?></h1>
        <p style="color: #cbd5e1; font-size: 0.95rem;"><?= htmlspecialchars($playlist['description'] ?: 'No description.') ?></p>
        <span style="font-size: 0.82rem; color: #94a3b8; margin-top: 10px; display: inline-block;">
            <i class="fa-solid fa-music"></i> <span id="song-count-span"><?= count($songs) ?></span> songs &bull; Created <?= date('M d, Y', strtotime($playlist['created_at'])) ?>
        </span>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openAddSongModal()">
            <i class="fa-solid fa-plus"></i> Add Songs
        </button>
    </div>
</div>

<div class="page-header">
    <h2 style="font-size: 1.3rem; color: #fff;">Songs in this Playlist</h2>
</div>

<?php if (empty($songs)): ?>
    <div class="empty-state" id="empty-state-box">
        <i class="fa-solid fa-compact-disc"></i>
        <h3>This playlist is empty</h3>
        <p>Start adding songs from the catalog to listen to your favorite tracks.</p>
        <button class="btn btn-primary" onclick="openAddSongModal()">
            <i class="fa-solid fa-plus"></i> Add Songs Now
        </button>
    </div>
    <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;"></div>
<?php else: ?>
    <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($songs as $song): 
            $s_id = $song['song_id'] ?? $song['id'];
            $s_title = $song['song_name'] ?? $song['title'];
            $s_artist = $song['artist_name'] ?? $song['artist'];
            $s_genre = $song['genre_name'] ?? $song['genre'];
        ?>
            <div class="song-card" id="song-row-<?= $s_id ?>">
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
                    <?php if (!empty($song['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($song['youtube_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Watch on YouTube">
                            <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> YouTube
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm" onclick="removeSong(<?= $playlist['id'] ?>, <?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                        <i class="fa-solid fa-xmark"></i> Remove
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Song to this Playlist Modal -->
<div id="add-song-modal" class="modal-overlay">
    <div class="modal-card" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Add Songs to "<?= htmlspecialchars($playlist['name']) ?>"</h3>
            <button class="modal-close" onclick="closeAddSongModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-hint" style="margin-bottom: 14px;">Select songs to add:</p>
            <div style="display: flex; flex-direction: column; gap: 10px; max-height: 350px; overflow-y: auto;">
                <?php if (empty($available_songs)): ?>
                    <p style="color: #9ca3af; text-align: center; padding: 20px;">All available songs are already in this playlist!</p>
                <?php else: ?>
                    <?php foreach ($available_songs as $avSong): 
                        $av_id = $avSong['song_id'] ?? $avSong['id'];
                        $av_title = $avSong['song_name'] ?? $avSong['title'];
                        $av_artist = $avSong['artist_name'] ?? $avSong['artist'];
                        $av_genre = $avSong['genre_name'] ?? $avSong['genre'];
                    ?>
                        <div class="song-card" style="padding: 10px 14px;" id="avail-card-<?= $av_id ?>">
                            <div class="song-info">
                                <div class="song-details">
                                    <span class="song-title" style="font-size: 0.9rem;"><?= htmlspecialchars($av_title) ?></span>
                                    <span class="song-artist" style="font-size: 0.8rem;"><?= htmlspecialchars($av_artist) ?> &bull; <?= htmlspecialchars($av_genre) ?></span>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="addSongToCurrentPlaylist(<?= $playlist['id'] ?>, <?= $av_id ?>)">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openAddSongModal() {
    document.getElementById('add-song-modal').style.display = 'flex';
}

function closeAddSongModal() {
    document.getElementById('add-song-modal').style.display = 'none';
}

function addSongToCurrentPlaylist(playlistId, songId) {
    const formData = new URLSearchParams();
    formData.append('playlist_id', playlistId);
    formData.append('song_id', songId);

    fetch('api/add_to_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Song added!', 'success');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error adding song', 'error'));
}

function removeSong(playlistId, songId, title) {
    if (!confirm(`Remove "${title}" from this playlist?`)) return;

    const formData = new URLSearchParams();
    formData.append('playlist_id', playlistId);
    formData.append('song_id', songId);

    fetch('api/remove_from_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const row = document.getElementById(`song-row-${songId}`);
            if (row) row.remove();
            
            const countEl = document.getElementById('song-count-span');
            if (countEl) {
                const current = parseInt(countEl.textContent, 10);
                countEl.textContent = Math.max(0, current - 1);
            }
            showToast('Song removed from playlist', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error removing song', 'error'));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
