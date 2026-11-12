<?php

include "../config/db.php";

header("Content-Type: application/json");

$sql = "SELECT
            artists.artist_id,
            artists.artist_name,
            artists.artist_image,
            COUNT(songs.song_id) AS total_songs

        FROM artists

        LEFT JOIN songs
        ON artists.artist_id = songs.artist_id

        GROUP BY artists.artist_id, artists.artist_name, artists.artist_image

        ORDER BY artists.artist_name";

$result = $conn->query($sql);

$artists = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $artists[] = $row;
    }
}

echo json_encode($artists);

?>