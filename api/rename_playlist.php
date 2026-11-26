<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please log in first."
    ]);
    exit();
}

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$playlist_id = isset($_POST['playlist_id']) ? (int)$_POST['playlist_id'] : 0;
$new_name = trim($_POST['playlist_name'] ?? $_POST['name'] ?? '');

if ($playlist_id <= 0 || empty($new_name)) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Valid playlist ID and new name are required']);
    exit;
}

$col_check = $conn->query("SHOW COLUMNS FROM playlists LIKE 'playlist_name'");
$name_col = ($col_check && $col_check->num_rows > 0) ? 'playlist_name' : 'name';

$stmt = $conn->prepare("UPDATE playlists SET $name_col = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $new_name, $playlist_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Playlist renamed successfully']);
    } else {
        echo json_encode(['success' => true, 'status' => 'info', 'message' => 'Name remained the same or playlist not found']);
    }
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Failed to rename playlist: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
