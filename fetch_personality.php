<?php
require_once __DIR__ . '/check_subscription.php';

function fetchUserTopGenre(mysqli $conn, int $user_id): ?array
{
    try {
        $stmt = $conn->prepare(
            "SELECT g.genre_id, g.genre_name, COUNT(lh.id) AS play_count
             FROM listening_history lh
             INNER JOIN songs s ON lh.song_id = s.song_id
             INNER JOIN genres g ON s.genre_id = g.genre_id
             WHERE lh.user_id = ?
             GROUP BY g.genre_id, g.genre_name
             ORDER BY play_count DESC, g.genre_name ASC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return [
            'genre_id' => (int)$row['genre_id'],
            'genre_name' => $row['genre_name'],
            'play_count' => (int)$row['play_count']
        ];
    } catch (Throwable $e) {
        return null;
    }
}

function fetchUserPersonality(mysqli $conn, int $user_id): array
{
    $topGenre = fetchUserTopGenre($conn, $user_id);

    if ($topGenre === null) {
        return [
            'has_history' => false,
            'top_genre' => null,
            'personality' => null,
            'fallback_reason' => 'no_history'
        ];
    }

    try {
        $stmt = $conn->prepare(
            "SELECT personality_id, personality_title, personality_description
             FROM genre_personality
             WHERE genre_id = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return [
                'has_history' => true,
                'top_genre' => $topGenre,
                'personality' => null,
                'fallback_reason' => 'no_mapping'
            ];
        }

        $genre_id = $topGenre['genre_id'];
        $stmt->bind_param('i', $genre_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $personality = $result->fetch_assoc();
        $stmt->close();

        if (!$personality) {
            return [
                'has_history' => true,
                'top_genre' => $topGenre,
                'personality' => null,
                'fallback_reason' => 'no_mapping'
            ];
        }

        $personality['personality_id'] = (int)$personality['personality_id'];

        return [
            'has_history' => true,
            'top_genre' => $topGenre,
            'personality' => $personality,
            'fallback_reason' => null
        ];
    } catch (Throwable $e) {
        return [
            'has_history' => true,
            'top_genre' => $topGenre,
            'personality' => null,
            'fallback_reason' => 'no_mapping'
        ];
    }
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    requirePremiumSubscription($conn, true);
    $user_id = (int)$_SESSION['user_id'];
    $data = fetchUserPersonality($conn, $user_id);

    header('Content-Type: application/json');
    echo json_encode(array_merge(['status' => 'success'], $data));
}
