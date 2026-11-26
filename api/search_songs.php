<?php

include "../config/db.php";

header("Content-Type: application/json");

if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    echo json_encode([]);
    exit;
}

$search = "%" . trim($_GET['q']) . "%";

$sql = "SELECT
            songs.song_id,
            songs.song_name,
            songs.album_name,
            songs.album_art,
            songs.preview_url,
            artists.artist_name,
            genres.genre_name

        FROM songs

        LEFT JOIN artists
        ON songs.artist_id = artists.artist_id

        LEFT JOIN genres
        ON songs.genre_id = genres.genre_id

        WHERE songs.song_name LIKE ?
        OR artists.artist_name LIKE ?
        OR songs.album_name LIKE ?

        ORDER BY songs.song_name";

$stmt = $conn->prepare($sql);

$stmt->bind_param("sss", $search, $search, $search);

$stmt->execute();

$result = $stmt->get_result();

$songs = [];

while ($row = $result->fetch_assoc()) {
    $songs[] = $row;
}

echo json_encode($songs);

?>