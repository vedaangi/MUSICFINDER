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
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;

if ($song_id <= 0) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Valid Song ID is required']);
    exit;
}

$check_col = $conn->query("SHOW COLUMNS FROM songs LIKE 'song_id'");
$col = ($check_col && $check_col->num_rows > 0) ? 'song_id' : 'id';

$check_song = $conn->prepare("SELECT $col FROM songs WHERE $col = ?");
$check_song->bind_param("i", $song_id);
$check_song->execute();
$check_song->store_result();

if ($check_song->num_rows === 0) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Song not found']);
    $check_song->close();
    exit;
}
$check_song->close();

$stmt = $conn->prepare("INSERT INTO listening_history (user_id, song_id, listened_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $song_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Listening history recorded']);
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Failed to record history: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
