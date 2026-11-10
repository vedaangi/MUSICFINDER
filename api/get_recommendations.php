<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

$user_id = getCurrentUserId();

// 1. Identify top genre from listening history
$genre_sql = "SELECT g.genre_name, COUNT(lh.id) AS play_count
              FROM listening_history lh
              JOIN songs s ON lh.song_id = s.song_id
              JOIN genres g ON s.genre_id = g.genre_id
              WHERE lh.user_id = ?
              GROUP BY g.genre_id, g.genre_name
              ORDER BY play_count DESC
              LIMIT 1";

$genre_stmt = $conn->prepare($genre_sql);
$genre_stmt->bind_param("i", $user_id);
$genre_stmt->execute();

$genre_result = $genre_stmt->get_result();

$top_genre = null;
$play_count = 0;

if ($row = $genre_result->fetch_assoc()) {
    $top_genre = $row['genre_name'];
    $play_count = (int)$row['play_count'];
}

$genre_stmt->close();

$recommendations = [];
$type = 'fallback';

// 2. Fetch songs in the user's top genre
if ($top_genre !== null) {

    $rec_sql = "SELECT
                    s.song_id,
                    s.song_name,
                    a.artist_name,
                    g.genre_name,
                    s.youtube_url,
                    s.album_name,
                    s.album_art,
                    s.preview_url
                FROM songs s
                LEFT JOIN artists a ON s.artist_id = a.artist_id
                LEFT JOIN genres g ON s.genre_id = g.genre_id
                WHERE s.genre_id = (
                    SELECT genre_id
                    FROM genres
                    WHERE genre_name = ?
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

    $rec_result = $rec_stmt->get_result();

    while ($song = $rec_result->fetch_assoc()) {
        $recommendations[] = $song;
    }

    $rec_stmt->close();

    if (!empty($recommendations)) {
        $type = 'personalized';
    }
}

// 3. Fallback recommendations
if (empty($recommendations)) {

    $fallback_sql = "SELECT
                        s.song_id,
                        s.song_name,
                        a.artist_name,
                        g.genre_name,
                        s.youtube_url,
                        s.album_name,
                        s.album_art,
                        s.preview_url
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

    $fb_stmt = $conn->prepare($fallback_sql);
    $fb_stmt->bind_param("i", $user_id);
    $fb_stmt->execute();

    $fb_result = $fb_stmt->get_result();

    while ($song = $fb_result->fetch_assoc()) {
        $recommendations[] = $song;
    }

    $fb_stmt->close();
}

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'top_genre' => $top_genre,
    'play_count' => $play_count,
    'recommendations' => $recommendations
]);

$conn->close();
?>
