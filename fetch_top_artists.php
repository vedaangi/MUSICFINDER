<?php
require_once __DIR__ . '/check_subscription.php';

function fetchTopArtists(mysqli $conn, int $user_id, int $limit = 5): array
{
    $artists = [];

    try {
        $stmt = $conn->prepare(
            "SELECT COALESCE(a.artist_id, 0) AS artist_id, 
                    COALESCE(a.artist_name, s.artist, 'Unknown Artist') AS artist_name, 
                    a.artist_image, 
                    COUNT(lh.id) AS play_count
             FROM listening_history lh
             INNER JOIN songs s ON (lh.song_id = s.song_id OR lh.song_id = s.id)
             LEFT JOIN artists a ON (s.artist_id = a.artist_id OR s.artist = a.artist_name)
             WHERE lh.user_id = ?
             GROUP BY COALESCE(a.artist_name, s.artist)
             ORDER BY play_count DESC, artist_name ASC
             LIMIT ?"
        );
        if (!$stmt) {
            return $artists;
        }

        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $artists[] = [
                'artist_id' => (int)$row['artist_id'],
                'artist_name' => $row['artist_name'],
                'artist_image' => $row['artist_image'],
                'play_count' => (int)$row['play_count']
            ];
        }

        $stmt->close();
    } catch (Throwable $e) {
        return [];
    }

    return $artists;
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    requirePremiumSubscription($conn, true);
    $user_id = (int)$_SESSION['user_id'];
    $artists = fetchTopArtists($conn, $user_id, 5);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'has_history' => !empty($artists),
        'artists' => $artists
    ]);
}
