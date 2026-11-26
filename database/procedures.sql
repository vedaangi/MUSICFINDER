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
    
    SELECT s.genre
    INTO v_genre
    FROM listening_history lh
    JOIN songs s ON lh.song_id = s.id
    WHERE lh.user_id = p_user_id
    GROUP BY s.genre
    ORDER BY COUNT(lh.id) DESC
    LIMIT 1;
    
    RETURN v_genre;
END$$

DROP PROCEDURE IF EXISTS sp_AddSongToPlaylist$$
CREATE PROCEDURE sp_AddSongToPlaylist(
    IN  p_playlist_id INT,
    IN  p_song_id     INT,
    OUT p_status      VARCHAR(50)
)
BEGIN
    DECLARE v_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_exists FROM playlists WHERE id = p_playlist_id;
    IF v_exists = 0 THEN
        SET p_status = 'PLAYLIST_NOT_FOUND';
    ELSE
        SELECT COUNT(*) INTO v_exists 
        FROM playlist_songs 
        WHERE playlist_id = p_playlist_id AND song_id = p_song_id;
        
        IF v_exists > 0 THEN
            SET p_status = 'DUPLICATE';
        ELSE
            INSERT INTO playlist_songs (playlist_id, song_id) 
            VALUES (p_playlist_id, p_song_id);
            SET p_status = 'SUCCESS';
        END IF;
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_GetUserTopGenre$$
CREATE PROCEDURE sp_GetUserTopGenre(
    IN  p_user_id    INT,
    OUT p_top_genre  VARCHAR(50),
    OUT p_play_count INT
)
BEGIN
    SET p_top_genre = NULL;
    SET p_play_count = 0;
    
    SELECT s.genre, COUNT(lh.id)
    INTO p_top_genre, p_play_count
    FROM listening_history lh
    JOIN songs s ON lh.song_id = s.id
    WHERE lh.user_id = p_user_id
    GROUP BY s.genre
    ORDER BY COUNT(lh.id) DESC
    LIMIT 1;
END$$

DELIMITER ;
