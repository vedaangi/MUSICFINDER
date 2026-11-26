<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

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
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="empty-state">
            <i class="fa-solid fa-lock" style="color: var(--accent);"></i>
            <h3>Playlist Not Found</h3>
            <p>This playlist does not exist or you do not have permission to access it.</p>
            <a href="playlists.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Return to Playlists</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$playlist = $playlist_res->fetch_assoc();
if (!isset($playlist['name']) && isset($playlist['playlist_name'])) {
    $playlist['name'] = $playlist['playlist_name'];
}
$stmt->close();

$songs = getPlaylistSongsList($conn, $playlist_id);
$available_songs = getAvailableSongsForPlaylist($conn, $playlist_id);
$user_fav_ids = getUserFavoriteSongIds($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <a href="playlists.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to My Playlists
    </a>
    <div style="display: flex; gap: 8px;">
        <button class="btn btn-secondary btn-sm" onclick="openRenameModal(<?= $playlist['id'] ?>, '<?= addslashes(htmlspecialchars($playlist['name'])) ?>')">
            <i class="fa-solid fa-pen"></i> Rename
        </button>
        <button class="btn btn-danger btn-sm" onclick="confirmDeletePlaylist(<?= $playlist['id'] ?>, '<?= addslashes(htmlspecialchars($playlist['name'])) ?>')">
            <i class="fa-solid fa-trash"></i> Delete
        </button>
    </div>
</div>

<div class="insight-banner">
    <div>
        <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); font-weight: 700;">Playlist</span>
        <h1 style="font-size: 1.85rem; color: var(--text-primary); margin: 6px 0;" id="view-pl-title"><?= htmlspecialchars($playlist['name']) ?></h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;"><?= htmlspecialchars($playlist['description'] ?: 'No description provided.') ?></p>
        <span style="font-size: 0.82rem; color: var(--text-muted); margin-top: 10px; display: inline-block;">
            <i class="fa-solid fa-music" style="color: var(--accent);"></i> <span id="song-count-span"><?= count($songs) ?></span> <?= count($songs) == 1 ? 'song' : 'songs' ?> &bull; Created <?= date('M d, Y', strtotime($playlist['created_at'])) ?>
        </span>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openAddSongModal()">
            <i class="fa-solid fa-plus"></i> Add Songs
        </button>
    </div>
</div>

<div class="page-header" style="margin-top: 24px;">
    <h2 style="font-size: 1.3rem; color: var(--text-primary);">Songs in this Playlist</h2>
</div>

<?php if (empty($songs)): ?>
    <div class="empty-state" id="empty-state-box">
        <i class="fa-solid fa-compact-disc"></i>
        <h3>This playlist is empty</h3>
        <p>Start adding songs from the catalog to build your perfect mix.</p>
        <button class="btn btn-primary" onclick="openAddSongModal()">
            <i class="fa-solid fa-plus"></i> Add Songs Now
        </button>
    </div>
    <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;"></div>
<?php else: ?>
    <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($songs as $song): 
            $s_id = (int)$song['song_id'];
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];
            $is_fav = in_array($s_id, $user_fav_ids);
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
                    <button class="btn btn-secondary btn-sm fav-toggle-btn fav-btn-<?= $s_id ?> <?= $is_fav ? 'active' : '' ?>" onclick="toggleFavorite(<?= $s_id ?>, this)" title="<?= $is_fav ? 'Unlike' : 'Like' ?>">
                        <i class="<?= $is_fav ? 'fa-solid' : 'fa-regular' ?> fa-heart" style="<?= $is_fav ? 'color: var(--accent);' : '' ?>"></i>
                    </button>
                    <?php if (!empty($song['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($song['youtube_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Watch on YouTube">
                            <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> <span class="hide-mobile">YouTube</span>
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
    <div class="modal-card" style="max-width: 620px;">
        <div class="modal-header">
            <h3>Add Songs to "<?= htmlspecialchars($playlist['name']) ?>"</h3>
            <button class="modal-close" onclick="closeAddSongModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="text" id="modal-song-search" placeholder="Search available songs by title or artist..." class="form-control" style="margin-bottom: 14px; width: 100%;" oninput="filterAvailableSongs()">
            <div id="modal-available-songs" style="display: flex; flex-direction: column; gap: 10px; max-height: 350px; overflow-y: auto;">
                <?php if (empty($available_songs)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 24px;">All available catalog songs are already in this playlist!</p>
                <?php else: ?>
                    <?php foreach ($available_songs as $avSong): 
                        $av_id = (int)$avSong['song_id'];
                        $av_title = $avSong['song_name'];
                        $av_artist = $avSong['artist_name'];
                        $av_genre = $avSong['genre_name'];
                    ?>
                        <div class="song-card avail-song-item" style="padding: 10px 14px;" id="avail-card-<?= $av_id ?>" data-title="<?= strtolower(htmlspecialchars($av_title)) ?>" data-artist="<?= strtolower(htmlspecialchars($av_artist)) ?>">
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

<!-- Rename Modal -->
<div id="rename-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Rename Playlist</h3>
            <button class="modal-close" onclick="closeRenameModal()">&times;</button>
        </div>
        <form onsubmit="handleRenamePlaylist(event)">
            <input type="hidden" id="rename-playlist-id" value="<?= $playlist['id'] ?>">
            <div class="form-group">
                <label for="rename-name">Playlist Name</label>
                <input type="text" id="rename-name" class="form-control" value="<?= htmlspecialchars($playlist['name']) ?>" required>
            </div>
            <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeRenameModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddSongModal() {
    document.getElementById('add-song-modal').style.display = 'flex';
}

function closeAddSongModal() {
    document.getElementById('add-song-modal').style.display = 'none';
}

function filterAvailableSongs() {
    const query = document.getElementById('modal-song-search').value.toLowerCase().trim();
    const items = document.querySelectorAll('.avail-song-item');
    items.forEach(item => {
        const title = item.getAttribute('data-title') || '';
        const artist = item.getAttribute('data-artist') || '';
        if (title.includes(query) || artist.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function openRenameModal(playlistId, currentName) {
    document.getElementById('rename-playlist-id').value = playlistId;
    document.getElementById('rename-name').value = currentName;
    document.getElementById('rename-modal').style.display = 'flex';
}

function closeRenameModal() {
    document.getElementById('rename-modal').style.display = 'none';
}

function handleRenamePlaylist(event) {
    event.preventDefault();
    const playlistId = document.getElementById('rename-playlist-id').value;
    const newName = document.getElementById('rename-name').value.trim();
    if (!newName) return;

    const formData = new URLSearchParams();
    formData.append('playlist_id', playlistId);
    formData.append('name', newName);

    fetch('api/rename_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('view-pl-title').textContent = newName;
            closeRenameModal();
            showToast('Playlist renamed successfully', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error renaming playlist', 'error'));
}

function confirmDeletePlaylist(playlistId, playlistName) {
    if (!confirm(`Are you sure you want to permanently delete "${playlistName}"?`)) return;

    const formData = new URLSearchParams();
    formData.append('playlist_id', playlistId);

    fetch('api/delete_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Playlist deleted', 'success');
            setTimeout(() => { window.location.href = 'playlists.php'; }, 500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error deleting playlist', 'error'));
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
            showToast('Song added to playlist!', 'success');
            setTimeout(() => window.location.reload(), 400);
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
