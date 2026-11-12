<?php

include "../config/db.php";

header("Content-Type: application/json");

$sql = "SELECT
            genres.genre_id,
            genres.genre_name,
            COUNT(songs.song_id) AS total_songs

        FROM genres

        LEFT JOIN songs
        ON genres.genre_id = songs.genre_id

        GROUP BY genres.genre_id, genres.genre_name

        ORDER BY genres.genre_name";

$result = $conn->query($sql);

$genres = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $genres[] = $row;
    }
}

echo json_encode($genres);

?>