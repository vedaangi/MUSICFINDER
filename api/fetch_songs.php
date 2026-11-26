
<?php

include "../config/db.php";

$sql = "SELECT
            songs.song_id,
            songs.song_name,
            songs.album_name,
            songs.album_art,
            songs.preview_url,
            songs.youtube_url,
            artists.artist_name,
            genres.genre_name

        FROM songs

        LEFT JOIN artists
        ON songs.artist_id = artists.artist_id

        LEFT JOIN genres
        ON songs.genre_id = genres.genre_id";

$result = $conn->query($sql);

$songs = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $songs[] = $row;
    }

}

header("Content-Type: application/json");

echo json_encode($songs);

$conn->close();

?>
