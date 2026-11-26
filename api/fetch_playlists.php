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

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT p.*, COUNT(ps.song_id) AS song_count
        FROM playlists p
        LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$playlists = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($row['name']) && isset($row['playlist_name'])) {
        $row['name'] = $row['playlist_name'];
    }
    $playlists[] = $row;
}

echo json_encode([
    'success' => true,
    'status' => 'success',
    'playlists' => $playlists
]);

$stmt->close();
$conn->close();
?>
