<?php
require_once __DIR__ . '/../config/db.php';

if (!function_exists('hasGenresTable')) {
    function hasGenresTable($conn) {
        static $has_genres = null;
        if ($has_genres !== null) return $has_genres;
        $res = $conn->query("SHOW TABLES LIKE 'genres'");
        $has_genres = ($res && $res->num_rows > 0);
        return $has_genres;
    }
}

if (!function_exists('getUserTopGenreInfo')) {
    function getUserTopGenreInfo($conn, $user_id) {
        $user_id = (int)$user_id;
        $has_genres = hasGenresTable($conn);

        if ($has_genres) {
            $sql = "SELECT COALESCE(g.genre_name, s.genre, 'None') AS genre_name, COUNT(lh.id) AS play_count
                    FROM listening_history lh
                    JOIN songs s ON lh.song_id = s.id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE lh.user_id = ?
                    GROUP BY genre_name
                    ORDER BY play_count DESC";
        } else {
            $sql = "SELECT COALESCE(s.genre, 'None') AS genre_name, COUNT(lh.id) AS play_count
                    FROM listening_history lh
                    JOIN songs s ON lh.song_id = s.id
                    WHERE lh.user_id = ?
                    GROUP BY s.genre
                    ORDER BY play_count DESC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['top_genre' => 'None', 'top_count' => 0, 'breakdown' => []];
        }

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $breakdown = [];
        $top_genre = 'None';
        $top_count = 0;
        $first = true;

        while ($row = $res->fetch_assoc()) {
            if ($first) {
                $top_genre = $row['genre_name'];
                $top_count = (int)$row['play_count'];
                $first = false;
            }
            $breakdown[] = [
                'genre' => $row['genre_name'],
                'count' => (int)$row['play_count']
            ];
        }
        $stmt->close();

        return [
            'top_genre' => $top_genre,
            'top_count' => $top_count,
            'breakdown' => $breakdown
        ];
    }
}

if (!function_exists('getPersonalizedRecommendations')) {
    function getPersonalizedRecommendations($conn, $user_id, $limit = 6) {
        $user_id = (int)$user_id;
        $limit = (int)$limit;
        $genre_info = getUserTopGenreInfo($conn, $user_id);
        $top_genre = $genre_info['top_genre'];

        $recommendations = [];
        $is_personalized = ($top_genre !== 'None' && $genre_info['top_count'] > 0);

        if ($is_personalized) {
            $sql = "SELECT s.id, COALESCE(s.title, s.song_name) AS title, COALESCE(s.artist, a.artist_name, 'Unknown') AS artist, COALESCE(s.genre, g.genre_name, 'Unknown') AS genre, s.youtube_url, s.preview_url, s.album_art
                    FROM songs s
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE (s.genre = ? OR g.genre_name = ?)
                    AND s.id NOT IN (
                        SELECT ps.song_id
                        FROM playlist_songs ps
                        JOIN playlists p ON ps.playlist_id = p.id
                        WHERE p.user_id = ?
                    )
                    ORDER BY s.id ASC
                    LIMIT ?";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssii", $top_genre, $top_genre, $user_id, $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $recommendations[] = $row;
                }
                $stmt->close();
            }
        }

        if (empty($recommendations)) {
            $sql = "SELECT s.id, COALESCE(s.title, s.song_name) AS title, COALESCE(s.artist, a.artist_name, 'Unknown') AS artist, COALESCE(s.genre, g.genre_name, 'Unknown') AS genre, s.youtube_url, s.preview_url, s.album_art
                    FROM songs s
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    ORDER BY s.id ASC
                    LIMIT ?";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $recommendations[] = $row;
                }
                $stmt->close();
            }
        }

        return [
            'top_genre' => $top_genre,
            'top_count' => $genre_info['top_count'],
            'breakdown' => $genre_info['breakdown'],
            'is_personalized' => $is_personalized,
            'recommendations' => $recommendations
        ];
    }
}
?>
