<?php
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

$user_id = getCurrentUserId();
$search = trim($_GET['q'] ?? '');
$genre_filter = trim($_GET['genre'] ?? '');
$sort = trim($_GET['sort'] ?? 'title_asc');

// Query songs with sorting support
$sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url FROM songs s WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (s.title LIKE ? OR s.artist LIKE ?)";
    $types .= 'ss';
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
}
if (!empty($genre_filter)) {
    $sql .= " AND s.genre = ?";
    $types .= 's';
    $params[] = $genre_filter;
}

switch ($sort) {
    case 'title_desc':
        $sql .= " ORDER BY s.title DESC";
        break;
    case 'artist_asc':
        $sql .= " ORDER BY s.artist ASC, s.title ASC";
        break;
    case 'genre_asc':
        $sql .= " ORDER BY s.genre ASC, s.title ASC";
        break;
    default:
        $sql .= " ORDER BY s.title ASC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$songs = [];
while ($row = $res->fetch_assoc()) $songs[] = $row;
$stmt->close();

$genres = getAllGenresList($conn);
$user_fav_ids = getUserFavoriteSongIds($conn, $user_id);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Music Catalog & Search</h1>
        <p class="page-subtitle">Browse, sort, and discover songs. Stream tracks, add them to your favorites or playlists.</p>
    </div>
</div>

<!-- Search, Filter & Sort Controls (Member 2 Module) -->
<div class="card" style="margin-bottom: 24px; padding: 20px;">
    <form method="GET" action="catalog.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 240px; position: relative;">
            <input type="text" name="q" class="form-control" placeholder="Search by song title or artist..." value="<?= htmlspecialchars($search) ?>" style="padding-left: 38px; width: 100%;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
        </div>

        <div style="min-width: 150px;">
            <select name="genre" class="form-control" onchange="this.form.submit()" style="width: 100%;">
                <option value="">All Genres</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>" <?= $genre_filter === $g ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="min-width: 160px;">
            <select name="sort" class="form-control" onchange="this.form.submit()" style="width: 100%;">
                <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Sort: Title (A-Z)</option>
                <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Sort: Title (Z-A)</option>
                <option value="artist_asc" <?= $sort === 'artist_asc' ? 'selected' : '' ?>>Sort: Artist (A-Z)</option>
                <option value="genre_asc" <?= $sort === 'genre_asc' ? 'selected' : '' ?>>Sort: Genre</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Apply
        </button>

        <?php if (!empty($search) || !empty($genre_filter) || $sort !== 'title_asc'): ?>
            <a href="catalog.php" class="btn btn-secondary" title="Reset all filters">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <!-- Genre Quick Pills -->
    <div style="display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
        <a href="catalog.php?sort=<?= urlencode($sort) ?>" class="genre-pill <?= empty($genre_filter) ? 'active' : '' ?>">All</a>
        <?php foreach ($genres as $g): ?>
            <a href="catalog.php?genre=<?= urlencode($g) ?>&sort=<?= urlencode($sort) ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" class="genre-pill <?= $genre_filter === $g ? 'active' : '' ?>">
                <?= htmlspecialchars($g) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <span style="font-size: 0.9rem; color: var(--text-secondary);">
        Found <strong style="color: var(--text-primary);"><?= count($songs) ?></strong> <?= count($songs) === 1 ? 'track' : 'tracks' ?>
        <?= !empty($genre_filter) ? 'in <span class="genre-tag">' . htmlspecialchars($genre_filter) . '</span>' : '' ?>
        <?= !empty($search) ? 'matching "<em>' . htmlspecialchars($search) . '</em>"' : '' ?>
    </span>
</div>

<?php if (empty($songs)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-music"></i>
        <h3>No songs found</h3>
        <p>No tracks matched your search and filter criteria. Try clearing filters to see the full music library.</p>
        <a href="catalog.php" class="btn btn-primary">Reset Filters</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($songs as $song): 
            $s_id = (int)$song['song_id'];
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];
            $is_fav = in_array($s_id, $user_fav_ids);
        ?>
            <div class="song-card">
                <div class="song-info">
                    <div class="song-play-icon" onclick="playSong(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= addslashes(htmlspecialchars($s_artist)) ?>', '<?= htmlspecialchars($song['youtube_url'] ?? '') ?>')" title="Play track">
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
                    <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                        <i class="fa-solid fa-plus"></i> <span class="hide-mobile">Playlist</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
