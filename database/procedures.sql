USE playlist;

DELIMITER $$

DROP FUNCTION IF EXISTS fn_GetPlaylistSongCount$$
CREATE FUNCTION fn_GetPlaylistSongCount(p_playlist_id INT) 
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    SELECT COUNT(*) 
    INTO v_count
    FROM playlist_songs
    WHERE playlist_id = p_playlist_id;
    
    RETURN v_count;
END$$

DROP FUNCTION IF EXISTS fn_GetUserTopGenre$$
CREATE FUNCTION fn_GetUserTopGenre(p_user_id INT) 
RETURNS VARCHAR(50)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_genre VARCHAR(50) DEFAULT 'None';
    
    SELECT COALESCE(s.genre, g.genre_name, 'None')
    INTO v_genre
    FROM listening_history lh
    JOIN songs s ON lh.song_id = s.id
    LEFT JOIN genres g ON s.genre_id = g.genre_id
    WHERE lh.user_id = p_user_id
    GROUP BY COALESCE(s.genre, g.genre_name)
    ORDER BY COUNT(lh.id) DESC
    LIMIT 1;
    
    RETURN v_genre;
END$$

DELIMITER ;
