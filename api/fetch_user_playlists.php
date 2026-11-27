<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in', 'playlists' => []]);
    exit;
}

$user_id = getCurrentUserId();

$sql = "SELECT p.id, p.name, p.description, COUNT(ps.id) as song_count 
        FROM playlists p 
        LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id 
        WHERE p.user_id = ? 
        GROUP BY p.id, p.name, p.description 
        ORDER BY p.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$playlists = [];
while ($row = $result->fetch_assoc()) {
    $playlists[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'song_count' => (int)$row['song_count']
    ];
}
$stmt->close();

echo json_encode(['status' => 'success', 'playlists' => $playlists]);
