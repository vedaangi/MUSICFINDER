<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = getCurrentUserId();
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Playlist name is required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO playlists (user_id, name, description) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $name, $description);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Playlist created successfully',
        'playlist_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create playlist: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
