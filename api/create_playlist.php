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
$name = trim($_POST['playlist_name'] ?? $_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Playlist name is required']);
    exit;
}

$col_check = $conn->query("SHOW COLUMNS FROM playlists LIKE 'playlist_name'");
$name_col = ($col_check && $col_check->num_rows > 0) ? 'playlist_name' : 'name';

$stmt = $conn->prepare("INSERT INTO playlists (user_id, $name_col, description) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $name, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'message' => 'Playlist created successfully',
        'playlist_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Failed to create playlist: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
