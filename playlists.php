<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();
require_once __DIR__ . '/config/db.php';

$user_id = (int)$_SESSION['user_id'];
$sort = trim($_GET['sort'] ?? 'newest');

$sql = "SELECT p.*, COUNT(ps.song_id) AS song_count
        FROM playlists p
        LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
        WHERE p.user_id = ?
        GROUP BY p.id";

switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;
    case 'songs_desc':
        $sql .= " ORDER BY song_count DESC, p.created_at DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$playlists = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($row['name']) && isset($row['playlist_name'])) {
        $row['name'] = $row['playlist_name'];
    }
    $playlists[] = $row;
}
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Playlists</h1>
        <p class="page-subtitle">Organize and curate your favorite tracks into personalized collections.</p>
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fa-solid fa-plus"></i> Create Playlist
    </button>
</div>

<?php if (empty($playlists)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-list-ul"></i>
        <h3>No playlists yet</h3>
        <p>You haven't created any playlists yet. Start organizing your music into custom mixes today!</p>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="fa-solid fa-plus"></i> Create Your First Playlist
        </button>
    </div>
<?php else: ?>
    <!-- Search and Sort Bar for Playlists -->
    <div class="card" style="margin-bottom: 24px; padding: 16px 20px;">
        <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            <div style="flex: 1; min-width: 220px; position: relative;">
                <input type="text" id="playlist-search-input" class="form-control" placeholder="Search your playlists..." oninput="filterPlaylistsLive()" style="padding-left: 38px; width: 100%;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            </div>
            <div style="min-width: 170px;">
                <form method="GET" action="playlists.php" id="sort-form">
                    <select name="sort" class="form-control" onchange="this.form.submit()" style="width: 100%;">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort: Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Sort: Oldest First</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Sort: Name (A-Z)</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Sort: Name (Z-A)</option>
                        <option value="songs_desc" <?= $sort === 'songs_desc' ? 'selected' : '' ?>>Sort: Most Songs</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <div class="grid-cards" id="playlists-grid">
        <?php foreach ($playlists as $pl): 
            $pl_title = $pl['playlist_name'] ?? $pl['name'];
            $pl_desc = $pl['description'] ?? '';
        ?>
            <div class="card playlist-item-card" id="playlist-card-<?= $pl['id'] ?>" data-title="<?= strtolower(htmlspecialchars($pl_title)) ?>" data-desc="<?= strtolower(htmlspecialchars($pl_desc)) ?>">
                <div>
                    <div class="playlist-card-top">
                        <div class="playlist-icon">
                            <i class="fa-solid fa-compact-disc"></i>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-secondary btn-sm" onclick="openRenameModal(<?= $pl['id'] ?>, '<?= addslashes(htmlspecialchars($pl_title)) ?>')" title="Rename Playlist">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="confirmDeletePlaylist(<?= $pl['id'] ?>, '<?= addslashes(htmlspecialchars($pl_title)) ?>')" title="Delete Playlist">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <h3 class="playlist-title" id="pl-title-<?= $pl['id'] ?>"><?= htmlspecialchars($pl_title) ?></h3>
                    <p class="playlist-desc"><?= htmlspecialchars($pl_desc ?: 'No description provided.') ?></p>
                </div>
                
                <div>
                    <div class="playlist-meta">
                        <span><i class="fa-solid fa-music" style="color: var(--accent);"></i> <?= $pl['song_count'] ?> <?= $pl['song_count'] == 1 ? 'song' : 'songs' ?></span>
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
                <textarea id="create-desc" class="form-control" rows="3" placeholder="Describe the vibe..."></textarea>
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
function filterPlaylistsLive() {
    const q = document.getElementById('playlist-search-input').value.toLowerCase().trim();
    const items = document.querySelectorAll('.playlist-item-card');
    items.forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const desc = card.getAttribute('data-desc') || '';
        if (title.includes(q) || desc.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

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
    formData.append('playlist_name', name);
    formData.append('name', name);
    formData.append('description', desc);

    fetch('api/create_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            closeCreateModal();
            showToast('Playlist created successfully!', 'success');
            setTimeout(() => window.location.reload(), 500);
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
    formData.append('playlist_name', newName);
    formData.append('name', newName);

    fetch('api/rename_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        closeRenameModal();
        if (data.success || data.status === 'success') {
            const titleEl = document.getElementById(`pl-title-${id}`);
            if (titleEl) titleEl.textContent = newName;
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
        if (data.success || data.status === 'success') {
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
