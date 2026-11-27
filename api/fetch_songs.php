
<?php

include "../config/db.php";

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
        
        ORDER BY songs.id ASC";

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
