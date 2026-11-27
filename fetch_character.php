<?php
require_once __DIR__ . '/check_subscription.php';
require_once __DIR__ . '/fetch_personality.php';

function fetchWeeklyCharacter(mysqli $conn, int $genre_id, ?string $date = null): ?array
{
    $targetDate = $date ?? date('Y-m-d');

    try {
        $stmt = $conn->prepare(
            "SELECT wm.weekly_id, wm.genre_id, g.genre_name, wm.character_name, wm.character_show,
                    wm.character_description, wm.character_image_url, wm.character_traits,
                    wm.character_music_match, wm.week_start, wm.week_end
             FROM weekly_movies wm
             LEFT JOIN genres g ON wm.genre_id = g.genre_id
             WHERE wm.genre_id = ?
               AND wm.week_start <= ?
               AND wm.week_end >= ?
             ORDER BY wm.week_start DESC, wm.weekly_id ASC
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
        $row['character_show'] = !empty($row['character_show']) ? $row['character_show'] : 'Featured Cinema / Series';
        $row['character_image_url'] = !empty($row['character_image_url']) ? $row['character_image_url'] : 'assets/images/placeholder_character.svg';
        $row['character_traits'] = !empty($row['character_traits']) ? $row['character_traits'] : 'Charismatic, thoughtful, artistic, and resilient.';
        if (empty($row['character_music_match'])) {
            $row['character_music_match'] = "This character reflects the heart and energy of your top-streamed songs, sharing a passion for authentic expression.";
        }

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
