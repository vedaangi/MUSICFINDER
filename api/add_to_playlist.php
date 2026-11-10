<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = getCurrentUserId();
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;

if ($playlist_id <= 0 || $song_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Playlist ID and Song ID are required']);
    exit;
}

// verify user owns this playlist
$check_owner = $conn->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
$check_owner->bind_param("ii", $playlist_id, $user_id);
$check_owner->execute();
$check_owner->store_result();

if ($check_owner->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Playlist not found or access denied']);
    $check_owner->close();
    exit;
}
$check_owner->close();

// check duplicate song in this playlist
$check_dup = $conn->prepare("SELECT id FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
$check_dup->bind_param("ii", $playlist_id, $song_id);
$check_dup->execute();
$check_dup->store_result();

if ($check_dup->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This song is already in the playlist']);
    $check_dup->close();
    exit;
}
$check_dup->close();

// insert song
$stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
$stmt->bind_param("ii", $playlist_id, $song_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Song added to playlist successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add song: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
