
<?php

include "../config/db.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Use POST method"]);
    exit;
}

$song_id = $_POST["song_id"] ?? "";
$song_name = trim($_POST["song_name"] ?? "");
$album_name = trim($_POST["album_name"] ?? "");
$artist_name = trim($_POST["artist_name"] ?? "");
$genre_name = trim($_POST["genre_name"] ?? "");

if (
    empty($song_id) ||
    empty($song_name) ||
    empty($artist_name)
) {
    echo json_encode(["error" => "Song ID, song name and artist are required"]);
    exit;
}

// Find or create artist
$stmt = $conn->prepare(
    "INSERT INTO artists (artist_name)
     VALUES (?)
     ON DUPLICATE KEY UPDATE artist_id = LAST_INSERT_ID(artist_id)"
);

$stmt->bind_param("s", $artist_name);
$stmt->execute();

$artist_id = $conn->insert_id;
$stmt->close();

// Find or create genre if provided
$genre_id = null;

if ($genre_name !== "") {

    $stmt = $conn->prepare(
        "INSERT INTO genres (genre_name)
         VALUES (?)
         ON DUPLICATE KEY UPDATE genre_id = LAST_INSERT_ID(genre_id)"
    );

    $stmt->bind_param("s", $genre_name);
    $stmt->execute();

    $genre_id = $conn->insert_id;
    $stmt->close();
}

// Update song
$sql = "UPDATE songs
        SET song_name = ?,
            album_name = ?,
            artist_id = ?,
            genre_id = ?
        WHERE song_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssiii",
    $song_name,
    $album_name,
    $artist_id,
    $genre_id,
    $song_id
);

if ($stmt->execute()) {

    if ($stmt->affected_rows >= 0) {
        echo json_encode(["success" => "Song updated successfully"]);
    }

} else {
    echo json_encode(["error" => "Could not update song"]);
}

$stmt->close();

?>
