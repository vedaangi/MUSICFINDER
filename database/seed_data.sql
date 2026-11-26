USE playlist;

INSERT INTO users (id, username, email, password) VALUES
(1, 'alex_music', 'alex@example.com', '$2y$10$wT0XkQYJ31z7f2K1u5Wl8eF9R.o0lM1kQ1Z8p4x9n.O0Z9vY5wzXa'),
(2, 'sarah_grooves', 'sarah@example.com', '$2y$10$wT0XkQYJ31z7f2K1u5Wl8eF9R.o0lM1kQ1Z8p4x9n.O0Z9vY5wzXa')
ON DUPLICATE KEY UPDATE username = VALUES(username);

INSERT INTO genres (genre_id, genre_name) VALUES
(1, 'Pop'),
(2, 'Rock'),
(3, 'Hard Rock'),
(4, 'Hip-Hop'),
(5, 'Classical'),
(6, 'Jazz'),
(7, 'Electronic'),
(8, 'R&B'),
(9, 'Indie'),
(10, 'Country'),
(11, 'Bollywood'),
(12, 'Lo-Fi')
ON DUPLICATE KEY UPDATE genre_name = VALUES(genre_name);

INSERT INTO artists (artist_id, artist_name) VALUES
(1, 'The Neighbourhood'),
(2, 'Arctic Monkeys'),
(3, 'Glass Animals'),
(4, 'Queen'),
(5, 'The Weeknd'),
(6, 'Drake'),
(7, 'deadmau5'),
(8, 'Frank Sinatra')
ON DUPLICATE KEY UPDATE artist_name = VALUES(artist_name);

INSERT INTO songs (id, song_id, track_id, song_name, title, artist_id, artist, genre_id, genre, youtube_url) VALUES
(1, 1, '1001', 'Sweater Weather', 'Sweater Weather', 1, 'The Neighbourhood', 9, 'Indie', 'https://www.youtube.com/watch?v=GCdwKhTtNNw'),
(2, 2, '1002', 'Do I Wanna Know?', 'Do I Wanna Know?', 2, 'Arctic Monkeys', 9, 'Indie', 'https://www.youtube.com/watch?v=bpOSxM0rNPM'),
(3, 3, '1003', 'Heat Waves', 'Heat Waves', 3, 'Glass Animals', 9, 'Indie', 'https://www.youtube.com/watch?v=mRD0-GxqHVo'),
(4, 4, '1004', 'Bohemian Rhapsody', 'Bohemian Rhapsody', 4, 'Queen', 2, 'Rock', 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ'),
(5, 5, '1005', 'Blinding Lights', 'Blinding Lights', 5, 'The Weeknd', 1, 'Pop', 'https://www.youtube.com/watch?v=4NRXx6U8ABQ'),
(6, 6, '1006', 'Gods Plan', 'Gods Plan', 6, 'Drake', 4, 'Hip-Hop', 'https://www.youtube.com/watch?v=uxpDa-c-4Mc'),
(7, 7, '1007', 'Strobe', 'Strobe', 7, 'deadmau5', 7, 'Electronic', 'https://www.youtube.com/watch?v=tKi9Z-f6qX4'),
(8, 8, '1008', 'Fly Me to the Moon', 'Fly Me to the Moon', 8, 'Frank Sinatra', 6, 'Jazz', 'https://www.youtube.com/watch?v=ZEcqHA7dbwM')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO playlists (id, user_id, name, description) VALUES
(1, 1, 'Late Night Indie Vibes', 'Chill indie and alternative tracks.'),
(2, 1, 'All Time Rock Anthems', 'Classic rock songs.')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO playlist_songs (playlist_id, song_id) VALUES
(1, 1),
(1, 2),
(2, 4)
ON DUPLICATE KEY UPDATE song_id = VALUES(song_id);

INSERT INTO listening_history (user_id, song_id, listened_at) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 4, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 5, DATE_SUB(NOW(), INTERVAL 12 HOUR));
