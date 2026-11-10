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
$new_name = trim($_POST['name'] ?? '');

if ($playlist_id <= 0 || empty($new_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Valid playlist ID and new name are required']);
    exit;
}

$stmt = $conn->prepare("UPDATE playlists SET name = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_name, $playlist_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Playlist renamed successfully']);
    } else {
        echo json_encode(['status' => 'info', 'message' => 'Name remained the same or playlist not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to rename playlist: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
