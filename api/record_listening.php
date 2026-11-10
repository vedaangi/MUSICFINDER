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

$check_song = $conn->prepare("SELECT song_id FROM songs WHERE song_id = ?");
$check_song->bind_param("i", $song_id);
$check_song->execute();
$check_song->store_result();

if ($check_song->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Song not found']);
    $check_song->close();
    exit;
}
$check_song->close();

$stmt = $conn->prepare("INSERT INTO listening_history (user_id, song_id, listened_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $song_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Listening history recorded']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record history: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
