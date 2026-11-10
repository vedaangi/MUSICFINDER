<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session_helper.php';

$user_id = getCurrentUserId();

$genre_sql = "SELECT g.genre_name, COUNT(lh.id) AS play_count
              FROM listening_history lh
              JOIN songs s ON lh.song_id = s.song_id
              JOIN genres g ON s.genre_id = g.genre_id
              WHERE lh.user_id = ?
              GROUP BY g.genre_id, g.genre_name
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
    $rec_sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url, s.album_name, s.album_art
                FROM songs s
                LEFT JOIN artists a ON s.artist_id = a.artist_id
                LEFT JOIN genres g ON s.genre_id = g.genre_id
                WHERE s.genre_id = (
                    SELECT genre_id FROM genres WHERE genre_name = ?
                )
                AND s.song_id NOT IN (
                    SELECT ps.song_id
                    FROM playlist_songs ps
                    JOIN playlists p ON ps.playlist_id = p.id
                    WHERE p.user_id = ?
                )
                ORDER BY s.song_id DESC
                LIMIT 8";

    $rec_stmt = $conn->prepare($rec_sql);
    $rec_stmt->bind_param("si", $top_genre, $user_id);
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

if (empty($recommendations)) {
    $fb_sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url, s.album_name, s.album_art
               FROM songs s
               LEFT JOIN artists a ON s.artist_id = a.artist_id
               LEFT JOIN genres g ON s.genre_id = g.genre_id
               WHERE s.song_id NOT IN (
                   SELECT ps.song_id
                   FROM playlist_songs ps
                   JOIN playlists p ON ps.playlist_id = p.id
                   WHERE p.user_id = ?
               )
               ORDER BY s.song_id DESC
               LIMIT 8";

    $fb_stmt = $conn->prepare($fb_sql);
    $fb_stmt->bind_param("i", $user_id);
    $fb_stmt->execute();
    $fb_res = $fb_stmt->get_result();

    while ($song = $fb_res->fetch_assoc()) {
        $recommendations[] = $song;
    }
    $fb_stmt->close();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Personalized Recommendations</h1>
        <p class="page-subtitle">Curated tracks based on your top listened genres and playlist activity.</p>
    </div>
</div>

<?php if ($is_personalized): ?>
    <div class="insight-banner">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: #1db954; font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-fire"></i> Listening Affinity Detected
            </div>
            <h2 class="insight-title">Your Top Genre: <?= htmlspecialchars($top_genre) ?></h2>
            <p class="insight-text">
                Based on <?= $top_count ?> listens in your history, here are new <?= htmlspecialchars($top_genre) ?> tracks not yet in any of your playlists.
            </p>
            <div style="display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                <?php foreach ($genre_stats as $stat): ?>
                    <span style="font-size: 0.75rem; padding: 3px 10px; background: rgba(255,255,255,0.08); border-radius: 12px; color: #e2e8f0;">
                        <?= htmlspecialchars($stat['genre_name']) ?>: <?= $stat['play_count'] ?> plays
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="insight-badge">
            <?= htmlspecialchars($top_genre) ?>
        </div>
    </div>
<?php else: ?>
    <div class="insight-banner" style="background: linear-gradient(135deg, #242236 0%, #1e1b2e 100%); border-color: #3f395b;">
        <div>
            <div style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; color: #a78bfa; font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-sparkles"></i> Popular Suggestions
            </div>
            <h2 class="insight-title">Discover Something New</h2>
            <p class="insight-text">
                You have not built up enough listening history yet. Here are popular catalog tracks to get you started!
            </p>
        </div>
        <a href="index.php" class="btn btn-primary btn-sm">
            Listen to Songs
        </a>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2 style="font-size: 1.3rem; color: #fff;">
        <?= $is_personalized ? 'Recommended ' . htmlspecialchars($top_genre) . ' Tracks' : 'Popular Songs for You' ?>
    </h2>
</div>

<?php if (empty($recommendations)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-check-circle" style="color: #1db954;"></i>
        <h3>You are all caught up!</h3>
        <p>You have already added all recommended songs to your playlists. Browse the catalog to explore more music.</p>
        <a href="index.php" class="btn btn-primary">Browse Catalog</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($recommendations as $song): 
            $s_id = $song['song_id'] ?? $song['id'];
            $s_title = $song['song_name'] ?? $song['title'];
            $s_artist = $song['artist_name'] ?? $song['artist'];
            $s_genre = $song['genre_name'] ?? $song['genre'];
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
                    <?php if (!empty($song['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($song['youtube_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Watch on YouTube">
                            <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> YouTube
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" onclick="openAddToPlaylistModal(<?= $s_id ?>, '<?= addslashes(htmlspecialchars($s_title)) ?>')">
                        <i class="fa-solid fa-plus"></i> Add to Playlist
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
