<?php
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/db_helper.php';

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$is_logged_in = isLoggedIn();

// Counts
$playlist_count = 0;
$history_count = 0;
$fav_count = 0;
$top_genre = 'None';
$user_fav_ids = [];

if ($is_logged_in) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM playlists WHERE user_id = $user_id");
    if ($res && $row = $res->fetch_assoc()) $playlist_count = (int)$row['c'];

    $res = $conn->query("SELECT COUNT(*) AS c FROM listening_history WHERE user_id = $user_id");
    if ($res && $row = $res->fetch_assoc()) $history_count = (int)$row['c'];

    $res = $conn->query("SELECT COUNT(*) AS c FROM favorites WHERE user_id = $user_id");
    if ($res && $row = $res->fetch_assoc()) $fav_count = (int)$row['c'];

    $genre_info = getUserTopGenreInfo($conn, $user_id);
    $top_genre = $genre_info['top_genre'] ?? 'None';

    // Recent plays
    $recent_history = getUserHistoryList($conn, $user_id, 4);

    // Personalized recommendations
    $rec_data = getRecommendedSongsList($conn, $user_id, 4);
    $recommendations = $rec_data['recommendations'];

    $user_fav_ids = getUserFavoriteSongIds($conn, $user_id);
} else {
    $recent_history = [];
    $recommendations = [];
}

// Featured songs for discovery
$featured_songs = getAllSongsCatalog($conn, '', '');
$featured_preview = array_slice($featured_songs, 0, 6);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="margin-bottom: 24px;">
    <div>
        <h1 class="page-title">
            <?= $is_logged_in ? 'Welcome back, ' . htmlspecialchars($username) . '!' : 'Welcome to MusicFinder' ?>
        </h1>
        <p class="page-subtitle">Your personalized hub for music discovery, custom playlists, and listening analytics.</p>
    </div>
    <div>
        <?php if ($is_logged_in): ?>
            <a href="playlists.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> New Playlist
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['welcome'])): ?>
    <div style="padding: 14px 18px; background: #fdf2f8; border: 1px solid var(--border-subtle); border-radius: var(--radius); color: var(--accent-dark); font-size: 0.92rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-check" style="font-size: 1.2rem; color: var(--accent);"></i>
        <div>
            <strong>Account created successfully!</strong> Explore songs, build your mixes, and listen to great music.
        </div>
    </div>
<?php endif; ?>

<!-- Metric Cards -->
<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 32px;">
    <div class="card">
        <span style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-list-ul" style="color: var(--accent);"></i> My Playlists</span>
        <h2 style="font-size: 2rem; margin: 8px 0; color: var(--text-primary); font-weight: 700;"><?= $playlist_count ?></h2>
        <a href="playlists.php" class="btn btn-secondary btn-sm" style="width: fit-content;">Manage Playlists</a>
    </div>

    <div class="card">
        <span style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-heart" style="color: var(--accent);"></i> Liked Songs</span>
        <h2 style="font-size: 2rem; margin: 8px 0; color: var(--accent); font-weight: 700;"><?= $fav_count ?></h2>
        <a href="favorites.php" class="btn btn-secondary btn-sm" style="width: fit-content;">View Liked Songs</a>
    </div>

    <div class="card">
        <span style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-fire" style="color: var(--accent);"></i> Top Listened Genre</span>
        <h2 style="font-size: 1.6rem; margin: 8px 0; color: var(--accent-dark); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($top_genre) ?></h2>
        <a href="recommendations.php" class="btn btn-secondary btn-sm" style="width: fit-content;">Recommendations</a>
    </div>

    <div class="card">
        <span style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-headphones" style="color: var(--accent);"></i> Songs Streamed</span>
        <h2 style="font-size: 2rem; margin: 8px 0; color: var(--text-primary); font-weight: 700;"><?= $history_count ?></h2>
        <a href="history.php" class="btn btn-secondary btn-sm" style="width: fit-content;">View History</a>
    </div>
</div>

<!-- Recommended For You Section -->
<?php if (!empty($recommendations)): ?>
    <div style="margin-bottom: 36px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--accent); font-size: 1.2rem;"></i>
                <h2 style="font-size: 1.25rem; color: var(--text-primary); font-weight: 700;">Recommended For You (Top Genre: <?= htmlspecialchars($top_genre) ?>)</h2>
            </div>
            <a href="recommendations.php" style="color: var(--accent-dark); font-size: 0.88rem; text-decoration: none; font-weight: 600;">
                See All <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
            </a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
            <?php foreach ($recommendations as $song): 
                $s_id = (int)$song['song_id'];
                $s_title = $song['song_name'];
                $s_artist = $song['artist_name'];
                $s_genre = $song['genre_name'];
                $is_fav = in_array($s_id, $user_fav_ids);
            ?>
                <div class="card" style="padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <span class="genre-tag"><?= htmlspecialchars($s_genre) ?></span>
                            <div class="song-play-icon" style="width: 34px; height: 34px; font-size: 0.85rem;" onclick="playSong(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= addslashes(htmlspecialchars($s_artist)) ?>', '<?= htmlspecialchars($song['youtube_url'] ?? '') ?>')">
                                <i class="fa-solid fa-play"></i>
                            </div>
                        </div>
                        <h4 style="color: var(--text-primary); font-size: 1rem; margin-bottom: 4px;"><?= htmlspecialchars($s_title) ?></h4>
                        <span style="color: var(--text-secondary); font-size: 0.85rem;"><?= htmlspecialchars($s_artist) ?></span>
                    </div>
                    <div style="display: flex; gap: 8px; margin-top: 14px;">
                        <button class="btn btn-secondary btn-sm fav-toggle-btn fav-btn-<?= $s_id ?> <?= $is_fav ? 'active' : '' ?>" onclick="toggleFavorite(<?= $s_id ?>, this)" title="<?= $is_fav ? 'Unlike' : 'Like' ?>">
                            <i class="<?= $is_fav ? 'fa-solid' : 'fa-regular' ?> fa-heart" style="<?= $is_fav ? 'color: var(--accent);' : '' ?>"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center;" onclick="openAddToPlaylistModal(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                            <i class="fa-solid fa-plus"></i> Playlist
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Recently Played Section -->
<?php if (!empty($recent_history)): ?>
    <div style="margin-bottom: 36px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent); font-size: 1.2rem;"></i>
                <h2 style="font-size: 1.25rem; color: var(--text-primary); font-weight: 700;">Recently Played</h2>
            </div>
            <a href="history.php" style="color: var(--accent-dark); font-size: 0.88rem; text-decoration: none; font-weight: 600;">
                Full History <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
            </a>
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($recent_history as $item): 
                $s_id = (int)$item['song_id'];
                $s_title = $item['song_name'];
                $s_artist = $item['artist_name'];
                $s_genre = $item['genre_name'];
                $is_fav = in_array($s_id, $user_fav_ids);
            ?>
                <div class="song-card" style="padding: 10px 16px;">
                    <div class="song-info">
                        <div class="song-play-icon" onclick="playSong(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= addslashes(htmlspecialchars($s_artist)) ?>', '<?= htmlspecialchars($item['youtube_url'] ?? '') ?>')">
                            <i class="fa-solid fa-play"></i>
                        </div>
                        <div class="song-details">
                            <span class="song-title"><?= htmlspecialchars($s_title) ?></span>
                            <span class="song-artist"><?= htmlspecialchars($s_artist) ?> &bull; <span class="genre-tag" style="display: inline-block; margin-left: 4px;"><?= htmlspecialchars($s_genre) ?></span></span>
                        </div>
                    </div>
                    <div class="song-actions">
                        <button class="btn btn-secondary btn-sm fav-toggle-btn fav-btn-<?= $s_id ?> <?= $is_fav ? 'active' : '' ?>" onclick="toggleFavorite(<?= $s_id ?>, this)" title="<?= $is_fav ? 'Unlike' : 'Like' ?>">
                            <i class="<?= $is_fav ? 'fa-solid' : 'fa-regular' ?> fa-heart" style="<?= $is_fav ? 'color: var(--accent);' : '' ?>"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                            <i class="fa-solid fa-plus"></i> <span class="hide-mobile">Playlist</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Featured Catalog Tracks Section -->
<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-compact-disc" style="color: var(--accent); font-size: 1.2rem;"></i>
            <h2 style="font-size: 1.25rem; color: var(--text-primary); font-weight: 700;">Featured Catalog Tracks</h2>
        </div>
        <a href="catalog.php" style="color: var(--accent-dark); font-size: 0.88rem; text-decoration: none; font-weight: 600;">
            Browse All <?= count($featured_songs) ?> Tracks <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
        </a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php foreach ($featured_preview as $song): 
            $s_id = (int)$song['song_id'];
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];
            $is_fav = in_array($s_id, $user_fav_ids);
        ?>
            <div class="song-card">
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
                    <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                        <i class="fa-solid fa-plus"></i> <span class="hide-mobile">Playlist</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
