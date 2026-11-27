<?php
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/config/db.php';

// Fetch distinct genres for the filter dropdown
$genreQuery = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name ASC");
$genres = [];
if ($genreQuery) {
    while ($g = $genreQuery->fetch_assoc()) {
        $genres[] = $g;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Discover Music</h1>
        <p class="page-subtitle">Search millions of songs worldwide, preview tracks, watch on YouTube, and add your favorites to personal playlists.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="browseAllSongs()">
            <i class="fa-solid fa-compact-disc"></i> Seeded Library
        </button>
    </div>
</div>

<div class="form-card" style="margin-bottom: 25px; padding: 22px;">
    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 2; min-width: 280px; position: relative;">
            <input
                type="text"
                id="searchInput"
                class="form-control"
                placeholder="Search any song, artist, or album worldwide (e.g. Taylor Swift, Adele, BTS)..."
                onkeyup="if(event.key === 'Enter') searchGlobalMusic()"
                aria-label="Search songs or artists"
            >
        </div>
        <button type="button" class="btn btn-primary" onclick="searchGlobalMusic()">
            <i class="fa-solid fa-magnifying-glass"></i> Search
        </button>
        <button type="button" class="btn btn-secondary" onclick="browseAllSongs()">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </button>
    </div>

    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 16px; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 14px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="genreFilter" style="font-weight: 700; color: var(--text-secondary); font-size: 0.88rem;">Genre:</label>
            <select id="genreFilter" class="form-control" style="width: auto; min-width: 160px;" onchange="filterSongs()">
                <option value="">All Genres</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= htmlspecialchars($genre['genre_name']) ?>"><?= htmlspecialchars($genre['genre_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="artistFilter" style="font-weight: 700; color: var(--text-secondary); font-size: 0.88rem;">Artist:</label>
            <select id="artistFilter" class="form-control" style="width: auto; min-width: 180px;" onchange="filterArtists()">
                <option value="">All Artists</option>
            </select>
        </div>

        <div id="resultsCount" style="margin-left: auto; color: var(--text-muted); font-size: 0.85rem; font-weight: 600;"></div>
    </div>
</div>

<div id="results" class="grid-container">
    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-secondary);">
        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; color: var(--forest-200); margin-bottom: 12px;"></i>
        <p>Loading music catalog...</p>
    </div>
</div>

<!-- Modal for adding song to user playlist -->
<div id="playlistModal" class="modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="playlistModalSongTitle">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="margin: 0; color: #fff;"><i class="fa-solid fa-plus-circle" style="color: var(--forest-200);"></i> Add to Playlist</h3>
            <button type="button" class="modal-close" onclick="closePlaylistModal()" aria-label="Close modal">&times;</button>
        </div>
        <p id="playlistModalSongTitle" style="color: var(--text-secondary); margin-bottom: 16px; font-weight: 600; font-size: 0.95rem;"></p>
        <div class="form-group">
            <label for="userPlaylistSelect">Choose Target Playlist</label>
            <select id="userPlaylistSelect" class="form-control">
                <option value="">Loading your playlists...</option>
            </select>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closePlaylistModal()">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="confirmAddToPlaylistBtn" onclick="confirmAddToPlaylist()">Add to Playlist</button>
        </div>
    </div>
</div>

<script src="js/script.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
