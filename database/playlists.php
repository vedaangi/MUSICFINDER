<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session_helper.php';

$user_id = getCurrentUserId();

$sql = "SELECT p.id, p.name, p.description, p.created_at, COUNT(ps.song_id) AS song_count
        FROM playlists p
        LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$playlists = [];
while ($row = $result->fetch_assoc()) {
    $playlists[] = $row;
}
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Playlists</h1>
        <p class="page-subtitle">Create and organize your favorite songs into customized playlists.</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fa-solid fa-plus"></i> Create Playlist
    </button>
</div>

<?php if (empty($playlists)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-music"></i>
        <h3>No playlists yet</h3>
        <p>You haven't created any playlists yet. Start organizing your music today!</p>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="fa-solid fa-plus"></i> Create Your First Playlist
        </button>
    </div>
<?php else: ?>
    <div class="grid-cards">
        <?php foreach ($playlists as $pl): ?>
            <div class="card" id="playlist-card-<?= $pl['id'] ?>">
                <div>
                    <div class="playlist-card-top">
                        <div class="playlist-icon">
                            <i class="fa-solid fa-compact-disc"></i>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" onclick="openRenameModal(<?= $pl['id'] ?>, '<?= addslashes(htmlspecialchars($pl['name'])) ?>')" title="Rename Playlist">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDeletePlaylist(<?= $pl['id'] ?>, '<?= addslashes(htmlspecialchars($pl['name'])) ?>')" title="Delete Playlist">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <h3 class="playlist-title" id="pl-title-<?= $pl['id'] ?>"><?= htmlspecialchars($pl['name']) ?></h3>
                    <p class="playlist-desc"><?= htmlspecialchars($pl['description'] ?: 'No description provided.') ?></p>
                </div>
                
                <div>
                    <div class="playlist-meta">
                        <span><i class="fa-solid fa-music"></i> <?= $pl['song_count'] ?> songs</span>
                        <span><?= date('M d, Y', strtotime($pl['created_at'])) ?></span>
                    </div>
                    <div class="playlist-actions">
                        <a href="playlist_view.php?id=<?= $pl['id'] ?>" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                            <i class="fa-solid fa-folder-open"></i> Open Playlist
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Playlist Modal -->
<div id="create-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Create New Playlist</h3>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form id="create-form" onsubmit="handleCreatePlaylist(event)">
            <div class="form-group">
                <label for="create-name">Playlist Name</label>
                <input type="text" id="create-name" class="form-control" placeholder="e.g., Road Trip Classics" required>
            </div>
            <div class="form-group">
                <label for="create-desc">Description (Optional)</label>
                <textarea id="create-desc" class="form-control" rows="3" placeholder="Describe the mood or vibe..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Rename Playlist Modal -->
<div id="rename-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Rename Playlist</h3>
            <button class="modal-close" onclick="closeRenameModal()">&times;</button>
        </div>
        <form id="rename-form" onsubmit="handleRenamePlaylist(event)">
            <input type="hidden" id="rename-id">
            <div class="form-group">
                <label for="rename-name">New Name</label>
                <input type="text" id="rename-name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRenameModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('create-modal').style.display = 'flex';
    document.getElementById('create-name').focus();
}

function closeCreateModal() {
    document.getElementById('create-modal').style.display = 'none';
    document.getElementById('create-form').reset();
}

function handleCreatePlaylist(e) {
    e.preventDefault();
    const name = document.getElementById('create-name').value.trim();
    const desc = document.getElementById('create-desc').value.trim();

    if (!name) return;

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('description', desc);

    fetch('api/create_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            closeCreateModal();
            showToast('Playlist created successfully!', 'success');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error creating playlist', 'error'));
}

function openRenameModal(id, currentName) {
    document.getElementById('rename-id').value = id;
    document.getElementById('rename-name').value = currentName;
    document.getElementById('rename-modal').style.display = 'flex';
    document.getElementById('rename-name').focus();
}

function closeRenameModal() {
    document.getElementById('rename-modal').style.display = 'none';
}

function handleRenamePlaylist(e) {
    e.preventDefault();
    const id = document.getElementById('rename-id').value;
    const newName = document.getElementById('rename-name').value.trim();

    if (!newName) return;

    const formData = new URLSearchParams();
    formData.append('playlist_id', id);
    formData.append('name', newName);

    fetch('api/rename_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        closeRenameModal();
        if (data.status === 'success') {
            document.getElementById(`pl-title-${id}`).textContent = newName;
            showToast('Playlist renamed!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error renaming playlist', 'error'));
}

function confirmDeletePlaylist(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('playlist_id', id);

    fetch('api/delete_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById(`playlist-card-${id}`);
            if (card) card.remove();
            showToast('Playlist deleted', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Error deleting playlist', 'error'));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
