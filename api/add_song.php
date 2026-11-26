
<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Use POST method"]);
    exit;
}

// 1. Receive song information
$track_id = trim($_POST["track_id"] ?? "");
$song_name = trim($_POST["song_name"] ?? "");
$artist_name = trim($_POST["artist_name"] ?? "");
$album_name = $_POST["album_name"] ?? "";
$album_art = $_POST["album_art"] ?? "";
$preview_url = $_POST["preview_url"] ?? "";
$duration = (int)($_POST["duration_ms"] ?? 0);
$genre_name = trim($_POST["genre_name"] ?? "Unknown");

// 2. Generate YouTube search link
$youtube_url = "https://www.youtube.com/results?search_query=" .
    urlencode($song_name . " " . $artist_name);

// 3. Validate required information
if ($track_id === "" || $song_name === "" || $artist_name === "") {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing required song information"
    ]);
    exit;
}

if ($genre_name === "") {
    $genre_name = "Unknown";
}

$conn->begin_transaction();

try {

    // 4. Find or insert artist
    $stmt = $conn->prepare(
        "SELECT artist_id FROM artists WHERE artist_name = ?"
    );

    $stmt->bind_param("s", $artist_name);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $artist_id = $row["artist_id"];
    } else {
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO artists (artist_name) VALUES (?)"
        );

        $stmt->bind_param("s", $artist_name);
        $stmt->execute();

        $artist_id = $conn->insert_id;
    }

    $stmt->close();


    // 5. Find or insert genre
    $stmt = $conn->prepare(
        "SELECT genre_id FROM genres WHERE genre_name = ?"
    );

    $stmt->bind_param("s", $genre_name);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $genre_id = $row["genre_id"];
    } else {
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO genres (genre_name) VALUES (?)"
        );

        $stmt->bind_param("s", $genre_name);
        $stmt->execute();

        $genre_id = $conn->insert_id;
    }

    $stmt->close();


    // 6. Check whether song already exists
    $stmt = $conn->prepare(
        "SELECT song_id FROM songs WHERE track_id = ?"
    );

    $stmt->bind_param("s", $track_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $song_id = (int)$row["song_id"];

        $stmt->close();
        $conn->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Song already exists!",
            "song_id" => $song_id,
            "already_exists" => true
        ]);

        exit;
    }

    $stmt->close();


    // 7. Insert new song, including YouTube URL
    $stmt = $conn->prepare(
        "INSERT INTO songs
        (track_id, song_name, artist_id, genre_id,
         album_name, album_art, preview_url, duration_ms, youtube_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssiisssis",
        $track_id,
        $song_name,
        $artist_id,
        $genre_id,
        $album_name,
        $album_art,
        $preview_url,
        $duration,
        $youtube_url
    );

    $stmt->execute();

    // Get internal song_id
    $song_id = $conn->insert_id;

    $stmt->close();

    $conn->commit();


    // 8. Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Song saved successfully!",
        "song_id" => $song_id,
        "youtube_url" => $youtube_url,
        "already_exists" => false
    ]);

} catch (Throwable $e) {

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "error" => "Could not save song"
    ]);
}

$conn->close();

?>
