
<?php

include "../config/db.php";

header("Content-Type: application/json");

// Check if genre ID is provided
if (!isset($_GET["genre_id"]) || !is_numeric($_GET["genre_id"])) {
    echo json_encode([
        "error" => "Please provide a valid genre ID"
    ]);
    exit;
}

$genre_id = (int)$_GET["genre_id"];

// Fetch songs belonging to selected genre
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

        WHERE songs.genre_id = ?

        ORDER BY songs.song_name";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $genre_id);

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
