<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

$user_id = getCurrentUserId();

// 1. Identify top genre from listening history
$genre_sql = "SELECT s.genre, COUNT(lh.id) AS play_count
              FROM listening_history lh
              JOIN songs s ON lh.song_id = s.id
              WHERE lh.user_id = ?
              GROUP BY s.genre
              ORDER BY play_count DESC
              LIMIT 1";

$genre_stmt = $conn->prepare($genre_sql);
$genre_stmt->bind_param("i", $user_id);
$genre_stmt->execute();
$genre_result = $genre_stmt->get_result();

$top_genre = null;
$play_count = 0;

if ($row = $genre_result->fetch_assoc()) {
    $top_genre = $row['genre'];
    $play_count = (int)$row['play_count'];
}
$genre_stmt->close();

$recommendations = [];
$type = 'fallback';

if ($top_genre !== null) {
    // 2. Fetch songs in top genre, excluding songs already in user's playlists
    $rec_sql = "SELECT s.id, s.title, s.artist, s.genre, s.youtube_url
                FROM songs s
                WHERE s.genre = ?
                  AND s.id NOT IN (
                      SELECT ps.song_id
                      FROM playlist_songs ps
                      JOIN playlists p ON ps.playlist_id = p.id
                      WHERE p.user_id = ?
                  )
                ORDER BY s.id DESC
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

// Fallback if no history exists or all top genre songs are already in playlists
if (empty($recommendations)) {
    $fallback_sql = "SELECT s.id, s.title, s.artist, s.genre, s.youtube_url
                     FROM songs s
                     WHERE s.id NOT IN (
                         SELECT ps.song_id
                         FROM playlist_songs ps
                         JOIN playlists p ON ps.playlist_id = p.id
                         WHERE p.user_id = ?
                     )
                     ORDER BY s.id DESC
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
