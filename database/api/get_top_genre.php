<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session_helper.php';

$user_id = getCurrentUserId();

// calculate top genre using GROUP BY and COUNT
$sql = "SELECT s.genre, COUNT(lh.id) AS play_count
        FROM listening_history lh
        JOIN songs s ON lh.song_id = s.id
        WHERE lh.user_id = ?
        GROUP BY s.genre
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
        'genre' => $row['genre'],
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
