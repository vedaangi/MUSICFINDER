<?php
/**
 * Database Query Helper
 * Provides a standardized compatibility layer between Member 2's song catalog
 * and Members 3 & 4's playlist, history, and recommendation queries.
 */

if (!function_exists('hasGenresTable')) {
    function hasGenresTable($conn) {
        static $has_genres = null;
        if ($has_genres === null) {
            $check = $conn->query("SHOW TABLES LIKE 'genres'");
            $has_genres = ($check && $check->num_rows > 0);
        }
        return $has_genres;
    }
}

if (!function_exists('getSongSelectQuery')) {
    function getSongSelectQuery($conn, $alias = 's') {
        if (hasGenresTable($conn)) {
            return "SELECT {$alias}.song_id, {$alias}.song_name, a.artist_name, g.genre_name, {$alias}.youtube_url
                    FROM songs {$alias}
                    LEFT JOIN artists a ON {$alias}.artist_id = a.artist_id
                    LEFT JOIN genres g ON {$alias}.genre_id = g.genre_id";
        } else {
            return "SELECT {$alias}.id AS song_id, {$alias}.title AS song_name, {$alias}.artist AS artist_name, {$alias}.genre AS genre_name, {$alias}.youtube_url
                    FROM songs {$alias}";
        }
    }
}

if (!function_exists('getUserTopGenreInfo')) {
    function getUserTopGenreInfo($conn, $user_id) {
        $user_id = (int)$user_id;
        if (hasGenresTable($conn)) {
            $sql = "SELECT g.genre_name, COUNT(lh.id) AS play_count
                    FROM listening_history lh
                    JOIN songs s ON lh.song_id = s.song_id
                    JOIN genres g ON s.genre_id = g.genre_id
                    WHERE lh.user_id = ?
                    GROUP BY g.genre_id, g.genre_name
                    ORDER BY play_count DESC";
        } else {
            $sql = "SELECT s.genre AS genre_name, COUNT(lh.id) AS play_count
                    FROM listening_history lh
                    JOIN songs s ON lh.song_id = s.id
                    WHERE lh.user_id = ?
                    GROUP BY s.genre
                    ORDER BY play_count DESC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['top_genre' => null, 'top_count' => 0, 'breakdown' => []];

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $breakdown = [];
        $top_genre = null;
        $top_count = 0;

        while ($row = $result->fetch_assoc()) {
            $breakdown[] = [
                'genre' => $row['genre_name'],
                'play_count' => (int)$row['play_count']
            ];
        }
        $stmt->close();

        if (!empty($breakdown)) {
            $top_genre = $breakdown[0]['genre'];
            $top_count = $breakdown[0]['play_count'];
        }

        return [
            'top_genre' => $top_genre,
            'top_count' => $top_count,
            'breakdown' => $breakdown
        ];
    }
}

if (!function_exists('getRecommendedSongsList')) {
    function getRecommendedSongsList($conn, $user_id, $limit = 8) {
        $user_id = (int)$user_id;
        $genre_info = getUserTopGenreInfo($conn, $user_id);
        $top_genre = $genre_info['top_genre'];
        $has_genres = hasGenresTable($conn);

        $recommendations = [];
        $is_personalized = false;

        if ($top_genre !== null) {
            if ($has_genres) {
                $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url
                        FROM songs s
                        LEFT JOIN artists a ON s.artist_id = a.artist_id
                        LEFT JOIN genres g ON s.genre_id = g.genre_id
                        WHERE g.genre_name = ?
                          AND s.song_id NOT IN (
                              SELECT ps.song_id
                              FROM playlist_songs ps
                              JOIN playlists p ON ps.playlist_id = p.id
                              WHERE p.user_id = ?
                          )
                        ORDER BY s.song_id DESC
                        LIMIT ?";
            } else {
                $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url
                        FROM songs s
                        WHERE s.genre = ?
                          AND s.id NOT IN (
                              SELECT ps.song_id
                              FROM playlist_songs ps
                              JOIN playlists p ON ps.playlist_id = p.id
                              WHERE p.user_id = ?
                          )
                        ORDER BY s.id DESC
                        LIMIT ?";
            }

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sii", $top_genre, $user_id, $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $recommendations[] = $row;
                }
                $stmt->close();
                if (!empty($recommendations)) {
                    $is_personalized = true;
                }
            }
        }

        // Fallback: popular / recent tracks not yet in user's playlists
        if (empty($recommendations)) {
            if ($has_genres) {
                $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url
                        FROM songs s
                        LEFT JOIN artists a ON s.artist_id = a.artist_id
                        LEFT JOIN genres g ON s.genre_id = g.genre_id
                        WHERE s.song_id NOT IN (
                            SELECT ps.song_id
                            FROM playlist_songs ps
                            JOIN playlists p ON ps.playlist_id = p.id
                            WHERE p.user_id = ?
                        )
                        ORDER BY s.song_id ASC
                        LIMIT ?";
            } else {
                $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url
                        FROM songs s
                        WHERE s.id NOT IN (
                            SELECT ps.song_id
                            FROM playlist_songs ps
                            JOIN playlists p ON ps.playlist_id = p.id
                            WHERE p.user_id = ?
                        )
                        ORDER BY s.id ASC
                        LIMIT ?";
            }

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $user_id, $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $recommendations[] = $row;
                }
                $stmt->close();
            }

            // If user has added everything to their playlists, show all songs so page is never empty
            if (empty($recommendations)) {
                if ($has_genres) {
                    $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url
                            FROM songs s
                            LEFT JOIN artists a ON s.artist_id = a.artist_id
                            LEFT JOIN genres g ON s.genre_id = g.genre_id
                            ORDER BY s.song_id ASC
                            LIMIT ?";
                } else {
                    $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url
                            FROM songs s
                            ORDER BY s.id ASC
                            LIMIT ?";
                }
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

if (!function_exists('getPlaylistSongsList')) {
    function getPlaylistSongsList($conn, $playlist_id) {
        $playlist_id = (int)$playlist_id;
        $has_genres = hasGenresTable($conn);

        if ($has_genres) {
            $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url, ps.added_at
                    FROM playlist_songs ps
                    JOIN songs s ON ps.song_id = s.song_id
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE ps.playlist_id = ?
                    ORDER BY ps.added_at DESC";
        } else {
            $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url, ps.added_at
                    FROM playlist_songs ps
                    JOIN songs s ON ps.song_id = s.id
                    WHERE ps.playlist_id = ?
                    ORDER BY ps.added_at DESC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("i", $playlist_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $songs = [];
        while ($row = $res->fetch_assoc()) {
            $songs[] = $row;
        }
        $stmt->close();
        return $songs;
    }
}

if (!function_exists('getAvailableSongsForPlaylist')) {
    function getAvailableSongsForPlaylist($conn, $playlist_id) {
        $playlist_id = (int)$playlist_id;
        $has_genres = hasGenresTable($conn);

        if ($has_genres) {
            $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url
                    FROM songs s
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE s.song_id NOT IN (
                        SELECT song_id FROM playlist_songs WHERE playlist_id = ?
                    )
                    ORDER BY s.song_name ASC";
        } else {
            $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url
                    FROM songs s
                    WHERE s.id NOT IN (
                        SELECT song_id FROM playlist_songs WHERE playlist_id = ?
                    )
                    ORDER BY s.title ASC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("i", $playlist_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $songs = [];
        while ($row = $res->fetch_assoc()) {
            $songs[] = $row;
        }
        $stmt->close();
        return $songs;
    }
}

if (!function_exists('getUserHistoryList')) {
    function getUserHistoryList($conn, $user_id, $limit = 50) {
        $user_id = (int)$user_id;
        $limit = (int)$limit;
        $has_genres = hasGenresTable($conn);

        if ($has_genres) {
            $sql = "SELECT lh.id AS history_id, lh.listened_at, s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url
                    FROM listening_history lh
                    JOIN songs s ON lh.song_id = s.song_id
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE lh.user_id = ?
                    ORDER BY lh.listened_at DESC
                    LIMIT ?";
        } else {
            $sql = "SELECT lh.id AS history_id, lh.listened_at, s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url
                    FROM listening_history lh
                    JOIN songs s ON lh.song_id = s.id
                    WHERE lh.user_id = ?
                    ORDER BY lh.listened_at DESC
                    LIMIT ?";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();

        $history = [];
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        return $history;
    }
}

if (!function_exists('getAllSongsCatalog')) {
    function getAllSongsCatalog($conn, $search = '', $genre = '') {
        $has_genres = hasGenresTable($conn);
        $params = [];
        $types = '';

        if ($has_genres) {
            $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url
                    FROM songs s
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE 1=1";
            if (!empty($search)) {
                $sql .= " AND (s.song_name LIKE ? OR a.artist_name LIKE ?)";
                $types .= 'ss';
                $term = "%{$search}%";
                $params[] = $term;
                $params[] = $term;
            }
            if (!empty($genre)) {
                $sql .= " AND g.genre_name = ?";
                $types .= 's';
                $params[] = $genre;
            }
            $sql .= " ORDER BY s.song_name ASC";
        } else {
            $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url
                    FROM songs s
                    WHERE 1=1";
            if (!empty($search)) {
                $sql .= " AND (s.title LIKE ? OR s.artist LIKE ?)";
                $types .= 'ss';
                $term = "%{$search}%";
                $params[] = $term;
                $params[] = $term;
            }
            if (!empty($genre)) {
                $sql .= " AND s.genre = ?";
                $types .= 's';
                $params[] = $genre;
            }
            $sql .= " ORDER BY s.title ASC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $songs = [];
        while ($row = $res->fetch_assoc()) {
            $songs[] = $row;
        }
        $stmt->close();
        return $songs;
    }
}

if (!function_exists('getAllGenresList')) {
    function getAllGenresList($conn) {
        $has_genres = hasGenresTable($conn);
        if ($has_genres) {
            $res = $conn->query("SELECT genre_name FROM genres ORDER BY genre_name ASC");
        } else {
            $res = $conn->query("SELECT DISTINCT genre AS genre_name FROM songs WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC");
        }
        $genres = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $genres[] = $row['genre_name'];
            }
        }
        return $genres;
    }
}


if (!function_exists('getUserFavoriteSongIds')) {
    function getUserFavoriteSongIds($conn, $user_id) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) return [];
        
        $res = $conn->query("SELECT song_id FROM favorites WHERE user_id = $user_id");
        $favs = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $favs[] = (int)$row['song_id'];
            }
        }
        return $favs;
    }
}

if (!function_exists('getUserFavoritesList')) {
    function getUserFavoritesList($conn, $user_id) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) return [];

        $has_genres = hasGenresTable($conn);
        if ($has_genres) {
            $sql = "SELECT s.song_id, s.song_name, a.artist_name, g.genre_name, s.youtube_url, f.created_at AS favorited_at
                    FROM favorites f
                    JOIN songs s ON f.song_id = s.song_id
                    LEFT JOIN artists a ON s.artist_id = a.artist_id
                    LEFT JOIN genres g ON s.genre_id = g.genre_id
                    WHERE f.user_id = ?
                    ORDER BY f.created_at DESC";
        } else {
            $sql = "SELECT s.id AS song_id, s.title AS song_name, s.artist AS artist_name, s.genre AS genre_name, s.youtube_url, f.created_at AS favorited_at
                    FROM favorites f
                    JOIN songs s ON f.song_id = s.id
                    WHERE f.user_id = ?
                    ORDER BY f.created_at DESC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $favs = [];
        while ($row = $res->fetch_assoc()) {
            $favs[] = $row;
        }
        $stmt->close();
        return $favs;
    }
}

if (!function_exists('getListeningSummaryStats')) {
    function getListeningSummaryStats($conn, $user_id) {
        $user_id = (int)$user_id;
        $has_genres = hasGenresTable($conn);

        // Total plays
        $tot_res = $conn->query("SELECT COUNT(*) AS total_plays, COUNT(DISTINCT song_id) AS unique_songs FROM listening_history WHERE user_id = $user_id");
        $tot_row = $tot_res ? $tot_res->fetch_assoc() : ['total_plays' => 0, 'unique_songs' => 0];

        // Most played song
        if ($has_genres) {
            $mps_sql = "SELECT s.song_name, a.artist_name, COUNT(lh.id) AS play_count
                        FROM listening_history lh
                        JOIN songs s ON lh.song_id = s.song_id
                        LEFT JOIN artists a ON s.artist_id = a.artist_id
                        WHERE lh.user_id = ?
                        GROUP BY s.song_id, s.song_name, a.artist_name
                        ORDER BY play_count DESC
                        LIMIT 1";
        } else {
            $mps_sql = "SELECT s.title AS song_name, s.artist AS artist_name, COUNT(lh.id) AS play_count
                        FROM listening_history lh
                        JOIN songs s ON lh.song_id = s.id
                        WHERE lh.user_id = ?
                        GROUP BY s.id, s.title, s.artist
                        ORDER BY play_count DESC
                        LIMIT 1";
        }

        $mps_stmt = $conn->prepare($mps_sql);
        $most_played = null;
        if ($mps_stmt) {
            $mps_stmt->bind_param("i", $user_id);
            $mps_stmt->execute();
            $res = $mps_stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $most_played = $r;
            }
            $mps_stmt->close();
        }

        return [
            'total_plays' => (int)($tot_row['total_plays'] ?? 0),
            'unique_songs' => (int)($tot_row['unique_songs'] ?? 0),
            'most_played' => $most_played
        ];
    }
}
