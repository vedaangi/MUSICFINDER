<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = getCurrentUserId();
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;

if ($song_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Valid Song ID is required']);
    exit;
}

$check_song = $conn->prepare("SELECT id, song_id FROM songs WHERE song_id = ? OR id = ? LIMIT 1");
$check_song->bind_param("ii", $song_id, $song_id);
$check_song->execute();
$song_res = $check_song->get_result()->fetch_assoc();
$check_song->close();

if (!$song_res) {
    echo json_encode(['status' => 'error', 'message' => 'Song not found']);
    exit;
}
$canonical_song_id = (int)($song_res['song_id'] ?: $song_res['id']);

$stmt = $conn->prepare("INSERT INTO listening_history (user_id, song_id, listened_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $canonical_song_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Listening history recorded']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record history: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
