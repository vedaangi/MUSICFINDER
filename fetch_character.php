<?php
require_once __DIR__ . '/check_subscription.php';
require_once __DIR__ . '/fetch_personality.php';

function fetchWeeklyCharacter(mysqli $conn, int $genre_id, ?string $date = null): ?array
{
    $targetDate = $date ?? date('Y-m-d');

    try {
        $stmt = $conn->prepare(
            "SELECT weekly_id, genre_id, character_name, character_description, week_start, week_end
             FROM weekly_movies
             WHERE genre_id = ?
               AND week_start <= ?
               AND week_end >= ?
             ORDER BY week_start DESC, weekly_id ASC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('iss', $genre_id, $targetDate, $targetDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $row['weekly_id'] = (int)$row['weekly_id'];
        $row['genre_id'] = (int)$row['genre_id'];

        return $row;
    } catch (Throwable $e) {
        return null;
    }
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    requirePremiumSubscription($conn, true);
    $user_id = (int)$_SESSION['user_id'];
    $topGenre = fetchUserTopGenre($conn, $user_id);

    $character = null;
    if ($topGenre !== null) {
        $character = fetchWeeklyCharacter($conn, $topGenre['genre_id']);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'top_genre' => $topGenre,
        'character' => $character
    ]);
}
