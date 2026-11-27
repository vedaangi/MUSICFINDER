<?php

include "../config/db.php";

header("Content-Type: application/json");

if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    echo json_encode([]);
    exit;
}

$search = "%" . trim($_GET['q']) . "%";

$sql = "SELECT
            COALESCE(songs.song_id, songs.id) AS song_id,
            COALESCE(songs.song_name, songs.title) AS song_name,
            songs.album_name,
            songs.album_art,
            songs.preview_url,
            songs.youtube_url,
            songs.audio_file,
            COALESCE(artists.artist_name, songs.artist, 'Unknown Artist') AS artist_name,
            COALESCE(genres.genre_name, songs.genre, 'Unknown') AS genre_name

        FROM songs

        LEFT JOIN artists
        ON songs.artist_id = artists.artist_id

        LEFT JOIN genres
        ON songs.genre_id = genres.genre_id

        WHERE songs.song_name LIKE ?
        OR songs.title LIKE ?
        OR artists.artist_name LIKE ?
        OR songs.artist LIKE ?
        OR songs.album_name LIKE ?

        ORDER BY COALESCE(songs.song_name, songs.title) ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $search, $search, $search, $search, $search);

$stmt->execute();

$result = $stmt->get_result();

$songs = [];

while ($row = $result->fetch_assoc()) {
    $songs[] = $row;
}

echo json_encode($songs);

?>