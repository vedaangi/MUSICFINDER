USE playlist;

INSERT INTO users (id, username, email, password) VALUES
(1, 'alex_music', 'alex@example.com', '$2y$10$wT0XkQYJ31z7f2K1u5Wl8eF9R.o0lM1kQ1Z8p4x9n.O0Z9vY5wzXa'),
(2, 'sarah_grooves', 'sarah@example.com', '$2y$10$wT0XkQYJ31z7f2K1u5Wl8eF9R.o0lM1kQ1Z8p4x9n.O0Z9vY5wzXa')
ON DUPLICATE KEY UPDATE username = VALUES(username);

INSERT INTO songs (id, title, artist, genre, youtube_url) VALUES
(1, 'Sweater Weather', 'The Neighbourhood', 'Indie', 'https://www.youtube.com/watch?v=GCdwKhTtNNw'),
(2, 'Do I Wanna Know?', 'Arctic Monkeys', 'Indie', 'https://www.youtube.com/watch?v=bpOSxM0rNPM'),
(3, 'Heat Waves', 'Glass Animals', 'Indie', 'https://www.youtube.com/watch?v=mRD0-GxqHVo'),
(4, 'Riptide', 'Vance Joy', 'Indie', 'https://www.youtube.com/watch?v=uJ_1HMAGb4k'),
(5, 'Midnight City', 'M83', 'Indie', 'https://www.youtube.com/watch?v=dX3k_QDnzHE'),
(6, 'Bohemian Rhapsody', 'Queen', 'Rock', 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ'),
(7, 'Hotel California', 'Eagles', 'Rock', 'https://www.youtube.com/watch?v=09839DpTctU'),
(8, 'Smells Like Teen Spirit', 'Nirvana', 'Rock', 'https://www.youtube.com/watch?v=hTWKbfoikeg'),
(9, 'Sweet Child O Mine', 'Guns N Roses', 'Rock', 'https://www.youtube.com/watch?v=1w7OgIMMRc4'),
(10, 'Back in Black', 'AC/DC', 'Rock', 'https://www.youtube.com/watch?v=pAgnJDJN4VA'),
(11, 'Blinding Lights', 'The Weeknd', 'Pop', 'https://www.youtube.com/watch?v=4NRXx6U8ABQ'),
(12, 'Levitating', 'Dua Lipa', 'Pop', 'https://www.youtube.com/watch?v=TUVcZfQe-Kw'),
(13, 'As It Was', 'Harry Styles', 'Pop', 'https://www.youtube.com/watch?v=H5v3kku4y6Q'),
(14, 'Bad Guy', 'Billie Eilish', 'Pop', 'https://www.youtube.com/watch?v=DyDfgMOUjCI'),
(15, 'HUMBLE.', 'Kendrick Lamar', 'Hip-Hop', 'https://www.youtube.com/watch?v=tvTRZJ-4EyI'),
(16, 'Gods Plan', 'Drake', 'Hip-Hop', 'https://www.youtube.com/watch?v=uxpDa-c-4Mc'),
(17, 'Lose Yourself', 'Eminem', 'Hip-Hop', 'https://www.youtube.com/watch?v=_Yhyp-_hX2s'),
(18, 'Fly Me to the Moon', 'Frank Sinatra', 'Jazz', 'https://www.youtube.com/watch?v=ZEcqHA7dbwM'),
(19, 'Feeling Good', 'Nina Simone', 'Jazz', 'https://www.youtube.com/watch?v=D5Y11SGoHNQ'),
(20, 'Take Five', 'Dave Brubeck', 'Jazz', 'https://www.youtube.com/watch?v=tT9Eh8wNMkw'),
(21, 'Strobe', 'deadmau5', 'Electronic', 'https://www.youtube.com/watch?v=tKi9Z-f6qX4'),
(22, 'Clarity', 'Zedd ft. Foxes', 'Electronic', 'https://www.youtube.com/watch?v=IxxstCcJlsc'),
(23, 'Lofi Hip Hop Radio', 'Lofi Girl', 'Lo-Fi', 'https://www.youtube.com/watch?v=jfKfPfyJRdk')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO playlists (id, user_id, name, description) VALUES
(1, 1, 'Late Night Indie Vibes', 'Chill indie and alternative tracks.'),
(2, 1, 'All Time Rock Anthems', 'Classic rock songs.')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO playlist_songs (playlist_id, song_id) VALUES
(1, 1),
(1, 2),
(2, 6),
(2, 8)
ON DUPLICATE KEY UPDATE song_id = VALUES(song_id);

INSERT INTO listening_history (user_id, song_id, listened_at) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 6, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 11, DATE_SUB(NOW(), INTERVAL 2 HOUR));
