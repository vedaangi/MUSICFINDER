
<?php

include "../config/db.php";

header("Content-Type: application/json");

$artist_id = $_GET["artist_id"] ?? "";

if (empty($artist_id) || !is_numeric($artist_id)) {
    echo json_encode(["error" => "A valid Artist ID is required"]);
    exit;
}

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
        ON songs.genre_id = genres.genre_id

        WHERE songs.artist_id = ?

        ORDER BY songs.song_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $artist_id);
$stmt->execute();

$result = $stmt->get_result();

$songs = [];

while ($row = $result->fetch_assoc()) {
    $songs[] = $row;
}

echo json_encode($songs);

$stmt->close();
$conn->close();

?>
