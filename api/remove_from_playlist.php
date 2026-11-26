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

$stmt = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
$stmt->bind_param("ii", $playlist_id, $song_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Song removed from playlist']);
    } else {
        echo json_encode(['status' => 'info', 'message' => 'Song was not found in playlist']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to remove song: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
