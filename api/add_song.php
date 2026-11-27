<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "error" => "Method not allowed. Use POST."]);
    exit;
}

// 1. Receive song information
$track_id = trim($_POST["track_id"] ?? "");
$song_name = trim($_POST["song_name"] ?? "");
$artist_name = trim($_POST["artist_name"] ?? "");
$album_name = trim($_POST["album_name"] ?? "");
$album_art = trim($_POST["album_art"] ?? "");
$preview_url = trim($_POST["preview_url"] ?? "");
$duration = (int)($_POST["duration_ms"] ?? 0);
$genre_name = trim($_POST["genre_name"] ?? "Unknown");

if ($genre_name === "") {
    $genre_name = "Unknown";
}

// Validate required fields
if ($song_name === "" || $artist_name === "") {
    http_response_code(400);
    echo json_encode(["status" => "error", "error" => "Song name and artist name are required."]);
    exit;
}

if ($track_id === "") {
    $track_id = "custom_" . substr(md5($song_name . "_" . $artist_name), 0, 12);
}

// 2. Generate valid YouTube URL
$youtube_url = !empty($_POST["youtube_url"]) ? trim($_POST["youtube_url"]) : 
    ("https://www.youtube.com/results?search_query=" . urlencode($song_name . " " . $artist_name));

$conn->begin_transaction();

try {
    // 3. Find or insert artist
    $stmt = $conn->prepare("SELECT artist_id FROM artists WHERE artist_name = ? LIMIT 1");
    $stmt->bind_param("s", $artist_name);
    $stmt->execute();
    $artist_res = $stmt->get_result();

    if ($row = $artist_res->fetch_assoc()) {
        $artist_id = (int)$row["artist_id"];
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO artists (artist_name) VALUES (?)");
        $stmt->bind_param("s", $artist_name);
        $stmt->execute();
        $artist_id = $conn->insert_id;
    }
    $stmt->close();

    // 4. Find or insert genre
    $stmt = $conn->prepare("SELECT genre_id FROM genres WHERE genre_name = ? LIMIT 1");
    $stmt->bind_param("s", $genre_name);
    $stmt->execute();
    $genre_res = $stmt->get_result();

    if ($row = $genre_res->fetch_assoc()) {
        $genre_id = (int)$row["genre_id"];
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO genres (genre_name) VALUES (?)");
        $stmt->bind_param("s", $genre_name);
        $stmt->execute();
        $genre_id = $conn->insert_id;
    }
    $stmt->close();

    // 5. Check if song already exists (by track_id OR by song_name + artist)
    $stmt = $conn->prepare(
        "SELECT id, song_id, youtube_url FROM songs 
         WHERE (track_id IS NOT NULL AND track_id <> '' AND track_id = ?) 
            OR (song_name = ? AND artist = ?) 
            OR (title = ? AND artist = ?)
         LIMIT 1"
    );
    $stmt->bind_param("sssss", $track_id, $song_name, $artist_name, $song_name, $artist_name);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $song_id = (int)($existing["song_id"] ?: $existing["id"]);
        $conn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Song is already in catalog.",
            "song_id" => $song_id,
            "youtube_url" => $existing["youtube_url"] ?: $youtube_url,
            "already_exists" => true
        ]);
        exit;
    }

    // 6. Insert new song with both relational and direct columns
    $stmt = $conn->prepare(
        "INSERT INTO songs
        (track_id, song_name, title, artist_id, artist, genre_id, genre,
         album_name, album_art, preview_url, duration_ms, youtube_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "sssissssssis",
        $track_id,
        $song_name,
        $song_name,
        $artist_id,
        $artist_name,
        $genre_id,
        $genre_name,
        $album_name,
        $album_art,
        $preview_url,
        $duration,
        $youtube_url
    );

    $stmt->execute();
    $new_id = $conn->insert_id;
    $stmt->close();

    // Ensure song_id matches id
    $conn->query("UPDATE songs SET song_id = id WHERE id = $new_id AND (song_id IS NULL OR song_id = 0)");

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Song added to catalog successfully!",
        "song_id" => $new_id,
        "youtube_url" => $youtube_url,
        "already_exists" => false
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "error" => "Could not save song: " . $e->getMessage()
    ]);
}

$conn->close();
?>
