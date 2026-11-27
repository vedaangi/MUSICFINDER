<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();
$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($playlist_id <= 0) {
    header('Location: playlists.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM playlists WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $playlist_id, $user_id);
$stmt->execute();
$playlist_res = $stmt->get_result();

if ($playlist_res->num_rows === 0) {
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="empty-state">
        <i class="fa-solid fa-lock" style="color: var(--danger);"></i>
        <h3>Playlist Not Found or Access Denied</h3>
        <p>You do not have permission to view this playlist, or it has been removed.</p>
        <a href="playlists.php" class="btn btn-primary">Back to My Playlists</a>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$playlist = $playlist_res->fetch_assoc();
$stmt->close();

$songs_sql = "SELECT s.id, s.song_id, COALESCE(s.song_name, s.title) as song_name, 
                     COALESCE(a.artist_name, s.artist, 'Unknown Artist') as artist_name, 
                     COALESCE(g.genre_name, s.genre, 'General') as genre_name, 
                     s.youtube_url, s.preview_url, s.album_art, ps.added_at 
              FROM playlist_songs ps
              JOIN songs s ON (ps.song_id = s.id OR ps.song_id = s.song_id)
              LEFT JOIN artists a ON s.artist_id = a.artist_id
              LEFT JOIN genres g ON s.genre_id = g.genre_id
              WHERE ps.playlist_id = ?
              GROUP BY s.id
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

$avail_sql = "SELECT s.id, s.song_id, COALESCE(s.song_name, s.title) as song_name, 
                     COALESCE(a.artist_name, s.artist, 'Unknown Artist') as artist_name, 
                     COALESCE(g.genre_name, s.genre, 'General') as genre_name, 
                     s.youtube_url 
              FROM songs s 
              LEFT JOIN artists a ON s.artist_id = a.artist_id 
              LEFT JOIN genres g ON s.genre_id = g.genre_id 
              WHERE s.id NOT IN (SELECT song_id FROM playlist_songs WHERE playlist_id = ?)
                AND (s.song_id IS NULL OR s.song_id NOT IN (SELECT song_id FROM playlist_songs WHERE playlist_id = ?))
              ORDER BY COALESCE(s.song_name, s.title) ASC";
$avail_stmt = $conn->prepare($avail_sql);
$avail_stmt->bind_param("ii", $playlist_id, $playlist_id);
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

<div class="insight-banner">
    <div>
        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--forest-100); font-weight: 700; margin-bottom: 4px;">
            <i class="fa-solid fa-compact-disc"></i> Personal Playlist
        </div>
        <h1 style="font-size: 2rem; color: #fff; margin: 4px 0 8px 0;" id="view-pl-title"><?= htmlspecialchars($playlist['name']) ?></h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;"><?= htmlspecialchars($playlist['description'] ?: 'No description provided.') ?></p>
        <span style="font-size: 0.82rem; color: var(--text-muted); margin-top: 10px; display: inline-block;">
            <i class="fa-solid fa-music"></i> <span id="song-count-span"><?= count($songs) ?></span> songs &bull; Created <?= date('M d, Y', strtotime($playlist['created_at'])) ?>
        </span>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openAddSongModal()">
            <i class="fa-solid fa-plus"></i> Add Songs
        </button>
    </div>
</div>

<div class="page-header">
    <h2 style="font-size: 1.3rem; color: #fff; margin: 0;">Playlist Tracks</h2>
</div>

<?php if (empty($songs)): ?>
    <div class="empty-state" id="empty-state-box">
        <i class="fa-solid fa-compact-disc"></i>
        <h3>This playlist is currently empty</h3>
        <p>Pick songs from the catalog below to add them to "<?= htmlspecialchars($playlist['name']) ?>".</p>
        <button type="button" class="btn btn-primary" onclick="openAddSongModal()">
            <i class="fa-solid fa-plus"></i> Add Songs Now
        </button>
    </div>
    <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;"></div>
<?php else: ?>
    <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($songs as $song): 
            $s_id = (int)($song['song_id'] ?: $song['id']);
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];

            $has_direct_yt = !empty($song['youtube_url']) && (str_contains($song['youtube_url'], 'youtube.com/watch') || str_contains($song['youtube_url'], 'youtu.be/'));
            $yt_link = !empty($song['youtube_url']) ? $song['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($s_title . ' ' . $s_artist . ' audio'));
            $yt_tooltip = $has_direct_yt ? ('Watch "' . htmlspecialchars($s_title) . '" on YouTube') : ('Find "' . htmlspecialchars($s_title) . '" on YouTube');
        ?>
            <div class="song-card-row" id="song-row-<?= $s_id ?>">
                <div class="song-info">
                    <div class="song-play-icon" style="cursor: pointer;" onclick="playTrackVideo('<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= htmlspecialchars($song['youtube_url'] ?? '') ?>', <?= $s_id ?>)" title="Play Preview">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($s_title) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($s_artist) ?></span>
                        <span class="genre-tag" style="width: fit-content; margin-top: 4px;"><?= htmlspecialchars($s_genre) ?></span>
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
                        <button type="button" class="btn btn-secondary btn-sm" onclick="playTrackVideo('<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= htmlspecialchars($song['youtube_url']) ?>', <?= $s_id ?>)" title="Open player">
                            <i class="fa-solid fa-play"></i> Player
                        </button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSong(<?= $playlist['id'] ?>, <?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')" title="Remove from playlist">
                        <i class="fa-solid fa-xmark"></i> Remove
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Song Modal -->
<div id="add-song-modal" class="modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="addSongModalHeading">
    <div class="modal-card" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="addSongModalHeading" style="margin: 0; color: #fff;">Add Songs to "<?= htmlspecialchars($playlist['name']) ?>"</h3>
            <button type="button" class="modal-close" onclick="closeAddSongModal()" aria-label="Close modal">&times;</button>
        </div>
        <div>
            <p style="color: var(--text-secondary); margin-bottom: 14px; font-size: 0.92rem;">Select from available tracks in your catalog:</p>
            <div style="display: flex; flex-direction: column; gap: 10px; max-height: 360px; overflow-y: auto; padding-right: 4px;">
                <?php if (empty($available_songs)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 25px;">All available catalog songs are already in this playlist!</p>
                <?php else: ?>
                    <?php foreach ($available_songs as $avSong): 
                        $av_id = (int)($avSong['song_id'] ?: $avSong['id']);
                        $av_title = $avSong['song_name'];
                        $av_artist = $avSong['artist_name'];
                        $av_genre = $avSong['genre_name'];

                        $av_has_yt = !empty($avSong['youtube_url']) && (str_contains($avSong['youtube_url'], 'youtube.com/watch') || str_contains($avSong['youtube_url'], 'youtu.be/'));
                        $av_yt_link = !empty($avSong['youtube_url']) ? $avSong['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($av_title . ' ' . $av_artist . ' audio'));
                        $av_yt_tooltip = $av_has_yt ? ('Watch "' . htmlspecialchars($av_title) . '" on YouTube') : ('Find "' . htmlspecialchars($av_title) . '" on YouTube');
                    ?>
                        <div class="song-card-row" style="padding: 10px 14px;" id="avail-card-<?= $av_id ?>">
                            <div class="song-info">
                                <div class="song-details">
                                    <span class="song-title" style="font-size: 0.92rem;"><?= htmlspecialchars($av_title) ?></span>
                                    <span class="song-artist" style="font-size: 0.82rem;"><?= htmlspecialchars($av_artist) ?> &bull; <?= htmlspecialchars($av_genre) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <a href="<?= htmlspecialchars($av_yt_link) ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="btn-youtube-icon btn-sm" 
                                   title="<?= $av_yt_tooltip ?>" 
                                   aria-label="<?= $av_yt_tooltip ?>">
                                    <i class="fa-brands fa-youtube"></i>
                                </a>
                                <button type="button" class="btn btn-primary btn-sm" onclick="addSongToCurrentPlaylist(<?= $playlist['id'] ?>, <?= $av_id ?>)">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
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
            alert('Song added to playlist!');
            window.location.reload();
        } else {
            alert(data.message || 'Could not add song.');
        }
    })
    .catch(() => alert('Network error adding song.'));
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
            alert('Song removed from playlist.');
        } else {
            alert(data.message || 'Could not remove song.');
        }
    })
    .catch(() => alert('Network error removing song.'));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
