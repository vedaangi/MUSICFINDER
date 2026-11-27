<?php
require_once __DIR__ . '/check_subscription.php';
require_once __DIR__ . '/fetch_personality.php';

function fetchWeeklyMovie(mysqli $conn, int $genre_id, ?string $date = null): ?array
{
    $targetDate = $date ?? date('Y-m-d');

    try {
        $stmt = $conn->prepare(
            "SELECT wm.weekly_id, wm.genre_id, g.genre_name, wm.movie_name, wm.movie_year,
                    wm.movie_description, wm.movie_poster_url, wm.movie_music_fit, wm.week_start, wm.week_end
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
        $row['movie_year'] = isset($row['movie_year']) ? (int)$row['movie_year'] : 2023;
        $row['movie_poster_url'] = !empty($row['movie_poster_url']) ? $row['movie_poster_url'] : 'assets/images/placeholder_movie.svg';
        if (empty($row['movie_music_fit'])) {
            $row['movie_music_fit'] = "This film's cinematic soundtrack resonates closely with the themes, acoustic texture, and rhythm of your most-listened music.";
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

    $movie = null;
    if ($topGenre !== null) {
        $movie = fetchWeeklyMovie($conn, $topGenre['genre_id']);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'top_genre' => $topGenre,
        'movie' => $movie
    ]);
}
