<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

$user_id = getCurrentUserId();

$sql = "SELECT g.genre_name, COUNT(lh.id) AS play_count
        FROM listening_history lh
        JOIN songs s ON lh.song_id = s.song_id
        JOIN genres g ON s.genre_id = g.genre_id
        WHERE lh.user_id = ?
        GROUP BY g.genre_id, g.genre_name
        ORDER BY play_count DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$genres = [];
$top_genre = null;
$top_count = 0;

while ($row = $result->fetch_assoc()) {
    $genres[] = [
        'genre' => $row['genre_name'],
        'play_count' => (int)$row['play_count']
    ];
}

if (!empty($genres)) {
    $top_genre = $genres[0]['genre'];
    $top_count = $genres[0]['play_count'];
}

echo json_encode([
    'status' => 'success',
    'has_history' => !empty($genres),
    'top_genre' => $top_genre,
    'play_count' => $top_count,
    'breakdown' => $genres
]);

$stmt->close();
$conn->close();
?>
