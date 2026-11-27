<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();

// Find user's top genre from listening history
$genre_sql = "SELECT COALESCE(g.genre_name, s.genre) as genre_name, COUNT(lh.id) AS play_count
              FROM listening_history lh
              JOIN songs s ON (lh.song_id = s.song_id OR lh.song_id = s.id)
              LEFT JOIN genres g ON s.genre_id = g.genre_id
              WHERE lh.user_id = ?
              GROUP BY COALESCE(g.genre_name, s.genre)
              ORDER BY play_count DESC";

$genre_stmt = $conn->prepare($genre_sql);
$genre_stmt->bind_param("i", $user_id);
$genre_stmt->execute();
$genre_res = $genre_stmt->get_result();

$genre_stats = [];
while ($row = $genre_res->fetch_assoc()) {
    $genre_stats[] = $row;
}
$genre_stmt->close();

$top_genre = null;
$top_count = 0;
if (!empty($genre_stats)) {
    $top_genre = $genre_stats[0]['genre_name'];
    $top_count = (int)$genre_stats[0]['play_count'];
}

$recommendations = [];
$is_personalized = false;

if ($top_genre !== null) {
    $rec_sql = "SELECT s.id, s.song_id, COALESCE(s.song_name, s.title) as song_name, 
                       COALESCE(a.artist_name, s.artist, 'Unknown Artist') as artist_name, 
                       COALESCE(g.genre_name, s.genre, 'General') as genre_name, 
                       s.youtube_url, s.album_name, s.album_art
                FROM songs s
                LEFT JOIN artists a ON s.artist_id = a.artist_id
                LEFT JOIN genres g ON s.genre_id = g.genre_id
                WHERE (g.genre_name = ? OR s.genre = ?)
                AND s.id NOT IN (
                    SELECT ps.song_id
                    FROM playlist_songs ps
                    JOIN playlists p ON ps.playlist_id = p.id
                    WHERE p.user_id = ?
                )
                ORDER BY s.id DESC
                LIMIT 8";

    $rec_stmt = $conn->prepare($rec_sql);
    $rec_stmt->bind_param("ssi", $top_genre, $top_genre, $user_id);
    $rec_stmt->execute();
    $rec_res = $rec_stmt->get_result();

    while ($song = $rec_res->fetch_assoc()) {
        $recommendations[] = $song;
    }
    $rec_stmt->close();

    if (!empty($recommendations)) {
        $is_personalized = true;
    }
}

// Fallback recommendations if user has no listening history or all top genre songs are saved
if (empty($recommendations)) {
    $fb_sql = "SELECT s.id, s.song_id, COALESCE(s.song_name, s.title) as song_name, 
                      COALESCE(a.artist_name, s.artist, 'Unknown Artist') as artist_name, 
                      COALESCE(g.genre_name, s.genre, 'General') as genre_name, 
                      s.youtube_url, s.album_name, s.album_art
               FROM songs s
               LEFT JOIN artists a ON s.artist_id = a.artist_id
               LEFT JOIN genres g ON s.genre_id = g.genre_id
               ORDER BY s.id ASC
               LIMIT 8";

    $fb_res = $conn->query($fb_sql);
    if ($fb_res) {
        while ($song = $fb_res->fetch_assoc()) {
            $recommendations[] = $song;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Personalized Recommendations</h1>
        <p class="page-subtitle">Tracks chosen for you based on your listening history, favorite genres, and mood.</p>
    </div>
</div>

<?php if ($is_personalized): ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--forest-100); font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-sparkles"></i> Listening Affinity Detected
            </div>
            <h2 class="insight-title">Your Top Genre: <?= htmlspecialchars($top_genre) ?></h2>
            <p class="insight-text">
                Based on <?= $top_count ?> plays in your history, here are fresh <?= htmlspecialchars($top_genre) ?> tracks you might love.
            </p>
            <div style="display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                <?php foreach ($genre_stats as $stat): ?>
                    <span class="genre-tag" style="background: rgba(44, 38, 63, 0.6); border: 1px solid var(--border-subtle);">
                        <?= htmlspecialchars($stat['genre_name']) ?>: <?= $stat['play_count'] ?> plays
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="insight-badge">
            <?= htmlspecialchars(strtoupper($top_genre)) ?>
        </div>
    </div>
<?php else: ?>
    <div class="insight-banner" style="background: var(--bg-card); border-color: var(--border-subtle);">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: var(--forest-200); font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-compass"></i> Popular Suggestions
            </div>
            <h2 class="insight-title">Discover Something New</h2>
            <p class="insight-text">
                As you stream more music, VARP will automatically tailor song suggestions to your favorite rhythms and artists.
            </p>
        </div>
        <a href="index.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-play"></i> Explore Music
        </a>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2 style="font-size: 1.3rem; color: #fff; margin: 0;">
        <?= $is_personalized ? 'Recommended ' . htmlspecialchars($top_genre) . ' Tracks' : 'Popular Songs for You' ?>
    </h2>
</div>

<?php if (empty($recommendations)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-check-circle" style="color: var(--success);"></i>
        <h3>You are all caught up!</h3>
        <p>No additional recommendations at this time. Browse the catalog to explore more music.</p>
        <a href="index.php" class="btn btn-primary">Browse Catalog</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($recommendations as $song): 
            $s_id = (int)($song['song_id'] ?: $song['id']);
            $s_title = $song['song_name'];
            $s_artist = $song['artist_name'];
            $s_genre = $song['genre_name'];

            $has_direct_yt = !empty($song['youtube_url']) && (str_contains($song['youtube_url'], 'youtube.com/watch') || str_contains($song['youtube_url'], 'youtu.be/'));
            $yt_link = !empty($song['youtube_url']) ? $song['youtube_url'] : ('https://www.youtube.com/results?search_query=' . urlencode($s_title . ' ' . $s_artist . ' audio'));
            $yt_tooltip = $has_direct_yt ? ('Watch "' . htmlspecialchars($s_title) . '" on YouTube') : ('Find "' . htmlspecialchars($s_title) . '" on YouTube');
        ?>
            <div class="song-card-row">
                <div class="song-info">
                    <div class="song-play-icon" style="cursor: pointer;" onclick="playTrackVideo('<?= addslashes(htmlspecialchars($s_title)) ?>', '<?= htmlspecialchars($song['youtube_url'] ?? '') ?>', <?= $s_id ?>)" title="Play Track">
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
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
