-- VARP Seed Data
-- Consolidated seeds for users, genres, artists, songs, playlists, history, personalities, weekly features, and subscriptions

USE playlist;

-- 1. Users (password for both is: password123)
INSERT INTO users (id, username, email, password, role) VALUES
(1, 'alex_music', 'alex@example.com', '$2y$10$wT0XkQYJ31z7f2K1u5Wl8eF9R.o0lM1kQ1Z8p4x9n.O0Z9vY5wzXa', 'admin'),
(2, 'sarah_grooves', 'sarah@example.com', '$2y$10$wT0XkQYJ31z7f2K1u5Wl8eF9R.o0lM1kQ1Z8p4x9n.O0Z9vY5wzXa', 'user')
ON DUPLICATE KEY UPDATE username = VALUES(username), role = VALUES(role);

-- 2. Genres
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

-- 3. Artists
INSERT INTO artists (artist_id, artist_name) VALUES
(1, 'The Neighbourhood'),
(2, 'Arctic Monkeys'),
(3, 'Glass Animals'),
(4, 'Queen'),
(5, 'The Weeknd'),
(6, 'Drake'),
(7, 'deadmau5'),
(8, 'Frank Sinatra'),
(9, 'Vance Joy'),
(10, 'M83'),
(11, 'Eagles'),
(12, 'Nirvana'),
(13, 'Guns N Roses'),
(14, 'AC/DC'),
(15, 'Dua Lipa'),
(16, 'Harry Styles'),
(17, 'Billie Eilish'),
(18, 'Kendrick Lamar'),
(19, 'Eminem'),
(20, 'Nina Simone'),
(21, 'Dave Brubeck'),
(22, 'Zedd ft. Foxes'),
(23, 'Lofi Girl')
ON DUPLICATE KEY UPDATE artist_name = VALUES(artist_name);

-- 4. Songs
INSERT INTO songs (id, song_id, track_id, song_name, title, artist_id, artist, genre_id, genre, album_art, youtube_url, audio_file) VALUES
(1, 1, '1001', 'Sweater Weather', 'Sweater Weather', 1, 'The Neighbourhood', 9, 'Indie', 'https://img.youtube.com/vi/GCdwKhTtNNw/hqdefault.jpg', 'https://www.youtube.com/watch?v=GCdwKhTtNNw', 'sweater_weather_preview.mp3'),
(2, 2, '1002', 'Do I Wanna Know?', 'Do I Wanna Know?', 2, 'Arctic Monkeys', 9, 'Indie', 'https://img.youtube.com/vi/bpOSxM0rNPM/hqdefault.jpg', 'https://www.youtube.com/watch?v=bpOSxM0rNPM', NULL),
(3, 3, '1003', 'Heat Waves', 'Heat Waves', 3, 'Glass Animals', 9, 'Indie', 'https://img.youtube.com/vi/mRD0-GxqHVo/hqdefault.jpg', 'https://www.youtube.com/watch?v=mRD0-GxqHVo', 'heat_waves_preview.mp3'),
(4, 4, '1004', 'Riptide', 'Riptide', 9, 'Vance Joy', 9, 'Indie', 'https://img.youtube.com/vi/uJ_1HMAGb4k/hqdefault.jpg', 'https://www.youtube.com/watch?v=uJ_1HMAGb4k', NULL),
(5, 5, '1005', 'Midnight City', 'Midnight City', 10, 'M83', 9, 'Indie', 'https://img.youtube.com/vi/dX3k_QDnzHE/hqdefault.jpg', 'https://www.youtube.com/watch?v=dX3k_QDnzHE', NULL),
(6, 6, '1006', 'Bohemian Rhapsody', 'Bohemian Rhapsody', 4, 'Queen', 2, 'Rock', 'https://img.youtube.com/vi/fJ9rUzIMcZQ/hqdefault.jpg', 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ', NULL),
(7, 7, '1007', 'Hotel California', 'Hotel California', 11, 'Eagles', 2, 'Rock', 'https://img.youtube.com/vi/09839DpTctU/hqdefault.jpg', 'https://www.youtube.com/watch?v=09839DpTctU', NULL),
(8, 8, '1008', 'Smells Like Teen Spirit', 'Smells Like Teen Spirit', 12, 'Nirvana', 2, 'Rock', 'https://img.youtube.com/vi/hTWKbfoikeg/hqdefault.jpg', 'https://www.youtube.com/watch?v=hTWKbfoikeg', NULL),
(9, 9, '1009', 'Sweet Child O Mine', 'Sweet Child O Mine', 13, 'Guns N Roses', 2, 'Rock', 'https://img.youtube.com/vi/1w7OgIMMRc4/hqdefault.jpg', 'https://www.youtube.com/watch?v=1w7OgIMMRc4', NULL),
(10, 10, '1010', 'Back in Black', 'Back in Black', 14, 'AC/DC', 2, 'Rock', 'https://img.youtube.com/vi/pAgnJDJN4VA/hqdefault.jpg', 'https://www.youtube.com/watch?v=pAgnJDJN4VA', NULL),
(11, 11, '1011', 'Blinding Lights', 'Blinding Lights', 5, 'The Weeknd', 1, 'Pop', 'https://img.youtube.com/vi/4NRXx6U8ABQ/hqdefault.jpg', 'https://www.youtube.com/watch?v=4NRXx6U8ABQ', NULL),
(12, 12, '1012', 'Levitating', 'Levitating', 15, 'Dua Lipa', 1, 'Pop', 'https://img.youtube.com/vi/TUVcZfQe-Kw/hqdefault.jpg', 'https://www.youtube.com/watch?v=TUVcZfQe-Kw', NULL),
(13, 13, '1013', 'As It Was', 'As It Was', 16, 'Harry Styles', 1, 'Pop', 'https://img.youtube.com/vi/H5v3kku4y6Q/hqdefault.jpg', 'https://www.youtube.com/watch?v=H5v3kku4y6Q', NULL),
(14, 14, '1014', 'Bad Guy', 'Bad Guy', 17, 'Billie Eilish', 1, 'Pop', 'https://img.youtube.com/vi/DyDfgMOUjCI/hqdefault.jpg', 'https://www.youtube.com/watch?v=DyDfgMOUjCI', NULL),
(15, 15, '1015', 'HUMBLE.', 'HUMBLE.', 18, 'Kendrick Lamar', 4, 'Hip-Hop', 'https://img.youtube.com/vi/tvTRZJ-4EyI/hqdefault.jpg', 'https://www.youtube.com/watch?v=tvTRZJ-4EyI', NULL),
(16, 16, '1016', 'Gods Plan', 'Gods Plan', 6, 'Drake', 4, 'Hip-Hop', 'https://img.youtube.com/vi/uxpDa-c-4Mc/hqdefault.jpg', 'https://www.youtube.com/watch?v=uxpDa-c-4Mc', NULL),
(17, 17, '1017', 'Lose Yourself', 'Lose Yourself', 19, 'Eminem', 4, 'Hip-Hop', 'https://img.youtube.com/vi/_Yhyp-_hX2s/hqdefault.jpg', 'https://www.youtube.com/watch?v=_Yhyp-_hX2s', NULL),
(18, 18, '1018', 'Fly Me to the Moon', 'Fly Me to the Moon', 8, 'Frank Sinatra', 6, 'Jazz', 'https://img.youtube.com/vi/ZEcqHA7dbwM/hqdefault.jpg', 'https://www.youtube.com/watch?v=ZEcqHA7dbwM', NULL),
(19, 19, '1019', 'Feeling Good', 'Feeling Good', 20, 'Nina Simone', 6, 'Jazz', 'https://img.youtube.com/vi/D5Y11S30H2k/hqdefault.jpg', 'https://www.youtube.com/watch?v=D5Y11S30H2k', NULL),
(20, 20, '1020', 'Take Five', 'Take Five', 21, 'Dave Brubeck', 6, 'Jazz', 'https://img.youtube.com/vi/vmDDOFXSgAs/hqdefault.jpg', 'https://www.youtube.com/watch?v=vmDDOFXSgAs', NULL),
(21, 21, '1021', 'Strobe', 'Strobe', 7, 'deadmau5', 7, 'Electronic', 'https://img.youtube.com/vi/tKi9Z-f6qX4/hqdefault.jpg', 'https://www.youtube.com/watch?v=tKi9Z-f6qX4', NULL),
(22, 22, '1022', 'Clarity', 'Clarity', 22, 'Zedd ft. Foxes', 7, 'Electronic', 'https://img.youtube.com/vi/IxxstCcJlsc/hqdefault.jpg', 'https://www.youtube.com/watch?v=IxxstCcJlsc', NULL),
(23, 23, '1023', 'Aesthetic Rain', 'Aesthetic Rain', 23, 'Lofi Girl', 12, 'Lo-Fi', 'https://img.youtube.com/vi/jfKfPfyJRdk/hqdefault.jpg', 'https://www.youtube.com/watch?v=jfKfPfyJRdk', NULL)
ON DUPLICATE KEY UPDATE title = VALUES(title), album_art = VALUES(album_art), audio_file = VALUES(audio_file);

-- 5. Playlists
INSERT INTO playlists (id, user_id, name, description) VALUES
(1, 1, 'Late Night Indie Vibes', 'Chill indie and alternative tracks.'),
(2, 1, 'All Time Rock Anthems', 'Classic rock songs.')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 6. Playlist songs
INSERT INTO playlist_songs (playlist_id, song_id) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 6),
(2, 7),
(2, 8)
ON DUPLICATE KEY UPDATE song_id = VALUES(song_id);

-- 7. Listening history (Alex has Indie as top genre)
INSERT INTO listening_history (user_id, song_id, listened_at) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 4, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 5, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 6, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 11, DATE_SUB(NOW(), INTERVAL 6 HOUR))
ON DUPLICATE KEY UPDATE song_id = VALUES(song_id);

-- 8. Subscriptions (Alex is an active premium subscriber)
INSERT INTO subscriptions (subscription_id, user_id, plan, start_date, end_date, status) VALUES
(1, 1, 'VARP Premium', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'active')
ON DUPLICATE KEY UPDATE status = VALUES(status), end_date = VALUES(end_date);

-- 9. Genre personality mappings
INSERT INTO genre_personality (genre_id, personality_title, personality_description) VALUES
(9, 'The Velvet Dreamer', 'You find magic in the understated. Your playlists are emotional journeys of soft nostalgia, dreamy synths, and poetic twilight thoughts.'),
(1, 'The Main Character', 'You live life in full color and high energy. Every sidewalk is your runway, every beat is a soundtrack moment, and you know how to bring glitter to any room.'),
(2, 'The Electric Rebel', 'Fierce, authentic, and unapologetic. You crave raw guitars, driving rhythm, and cathartic anthems that shake the world awake.'),
(3, 'The Thunder Catalyst', 'Bold and high-voltage. You thrive on heavy distortion, soaring vocals, and relentless sonic power that fuels your inner fire.'),
(4, 'The Rhythm Alchemist', 'Sharp, stylish, and deeply attuned to flow. You appreciate wordplay, hypnotic beats, and tracks that exude confidence and effortless swagger.'),
(5, 'The Symphony Sage', 'Harmonious, intricate, and deeply cultured. You appreciate grand dynamics, orchestral textures, and timeless compositions.'),
(6, 'The Midnight Philosopher', 'Smooth, sophisticated, and observant. You appreciate complex chord progressions, velvet saxophone solos, and the quiet beauty of late-night reflections.'),
(7, 'The Neon Visionary', 'Futuristic, vibrant, and always in motion. You thrive in pulse-raising drops, synth-wave horizons, and digital soundscapes that elevate your spirit.'),
(8, 'The Soulful Heart', 'Warm, expressive, and deeply romantic. You resonate with heartfelt vocal runs, lush harmonies, and velvet bass grooves.'),
(10, 'The Wildflower Nomad', 'Story-driven and grounded. You cherish acoustic picking, heartfelt lyricism, open highways, and honest campfire melodies.'),
(11, 'The Cinematic Star', 'Dazzling, expressive, and passionate. You celebrate sweeping strings, rhythmic tabla beats, and festive emotional grandeur.'),
(12, 'The Cozy Daydreamer', 'Warm, mellow, and peaceful. You love rainy afternoon coffee shops, gentle vinyl crackles, soft piano loops, and undisturbed flow states.')
ON DUPLICATE KEY UPDATE personality_title = VALUES(personality_title), personality_description = VALUES(personality_description);

-- 10. Weekly Movies & Characters (active across current and upcoming weeks - Famous Icons)
INSERT INTO weekly_movies (weekly_id, genre_id, movie_name, movie_year, movie_description, movie_poster_url, movie_music_fit, character_name, character_show, character_description, character_traits, character_music_match, character_image_url, week_start, week_end) VALUES
(1, 9, 'Scott Pilgrim vs. The World', 2010, 'A hyper-kinetic, graphic-novel rock comedy following a bass player fighting seven evil exes amidst buzzing underground garage rock battles.', 'assets/images/placeholder_movie.svg', 'Driven by distorted fuzz pedals, Beck\'s original acoustic-indie compositions, and Metric\'s electric melodies, this cult classic captures the bittersweet angst and energetic wit of your indie tracklist.', 'Ramona Flowers', 'Scott Pilgrim vs. The World', 'Enigmatic, fiercely independent, witty rollerblader with vivid color-changing hair and an unapologetic cool.', 'Enigmatic, fiercely independent, witty rollerblader with vivid color-changing hair and an unapologetic cool.', 'Ramona embodies the introspective coolness of indie music. Her effortless, genre-defying style and love for underground concerts match the dreamy, nonconformist energy of your top indie plays.', 'assets/images/characters/ramona_flowers.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(2, 1, 'Barbie', 2023, 'A dazzling, existential pop-culture spectacle exploring identity, vibrant sisterhood, and what it means to be human in the real world.', 'assets/images/placeholder_movie.svg', 'Packed with record-breaking pop sensations from Dua Lipa, Billie Eilish, and Charli XCX, the film celebrates bright tempo shifts, sparkling choruses, and unstoppable dance-floor joy.', 'Barbie', 'Barbie (2023)', 'Radiant, empathetic, fashion-forward, deeply curious, and discovering her genuine purpose.', 'Radiant, empathetic, fashion-forward, deeply curious, and discovering her genuine purpose.', 'Barbie is the ultimate celebration of radiant pop joy. Her journey of warmth, empowerment, and unapologetic self-expression mirrors the upbeat confidence of your favorite pop tracks.', 'assets/images/characters/barbie.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(3, 2, 'Stranger Things: Rock of the Upside Down', 2022, 'A pulse-pounding supernatural journey where heavy metal guitar solos literally hold back apocalyptic darkness.', 'assets/images/placeholder_movie.svg', 'Highlighted by legendary guitar solos like Metallica\'s "Master of Puppets" on a storm-lit trailer roof, this soundtrack pulses with pure, unrestrained electric rock voltage.', 'Eddie Munson', 'Stranger Things (Season 4)', 'Charismatic metalhead, fiercely loyal Dungeon Master, rebellious, passionate, and an unsung hero.', 'Charismatic metalhead, fiercely loyal Dungeon Master, rebellious, passionate, and an unsung hero.', 'Eddie wears his love for heavy riffs like armor. He represents the soul of classic rock: living authentically, defending outcasts, and letting every guitar strum bleed heartfelt emotion.', 'assets/images/characters/eddie_munson.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(4, 4, 'Spider-Man: Into the Spider-Verse', 2018, 'A visually electrifying animated adventure propelled by infectious hip-hop beats, cutting-edge art, and multiverse heroics.', 'assets/images/placeholder_movie.svg', 'Anchored by Post Malone & Swae Lee\'s "Sunflower", Vince Staples, and heavy 808 boom-bap, the soundtrack matches every leap of faith across the neon New York skyline.', 'Miles Morales', 'Spider-Man: Into the Spider-Verse', 'Creative street-artist, resilient Brooklyn teen, courageous, and finding his rhythm as his own hero.', 'Creative street-artist, resilient Brooklyn teen, courageous, and finding his rhythm as his own hero.', 'Miles uses hip-hop beats and spray paint to find his identity. His headphones are his compass, matching your appreciation for clever lyricism, rhythm, and bold swagger.', 'assets/images/characters/miles_morales.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(5, 6, 'La La Land', 2016, 'A dazzling tribute to classic jazz and romantic yearning set among the twilight glow of Los Angeles.', 'assets/images/placeholder_movie.svg', 'An Oscar-winning tapestry of wistful acoustic pianos, soaring trumpet solos, and twilight dance duets beneath the purple skies of Griffith Observatory.', 'Sebastian Wilder', 'La La Land', 'Passionate jazz purist, devoted pianist, romantic dreamer, and dedicated craftsman.', 'Passionate jazz purist, devoted pianist, romantic dreamer, and dedicated craftsman.', 'Sebastian lives for timeless improvisation and emotional chord progressions. His unyielding dedication to his craft mirrors your taste for rich acoustic instrumentation and midnight jazz.', 'assets/images/characters/sebastian_wilder.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(6, 7, 'The Matrix', 1999, 'A groundbreaking cyberpunk revolution propelled by industrial techno, breakbeats, and synth-laced virtual battles.', 'assets/images/placeholder_movie.svg', 'Powered by Rob Dougan\'s "Clubbed to Death", The Prodigy, and industrial breakbeats that redefined cinematic electronic music and club culture.', 'Trinity', 'The Matrix', 'Sleek cybernetic rebel, fearless hacker, tactical mastermind, and deeply devoted protector.', 'Sleek cybernetic rebel, fearless hacker, tactical mastermind, and deeply devoted protector.', 'Trinity moves with the speed, precision, and futuristic adrenaline of heavy synthesizer drops and cyber-techno beats, reflecting your high-tempo electronic taste.', 'assets/images/characters/trinity.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(7, 12, 'Spirited Away', 2001, 'A timeless Studio Ghibli masterpiece accompanied by Joe Hisaishi\'s breathtaking piano and acoustic ambient arrangements.', 'assets/images/placeholder_movie.svg', 'Joe Hisaishi\'s legendary Ghibli piano score ("One Summer\'s Day") and ambient bells provide the quintessential peaceful, meditative lo-fi acoustic sanctuary.', 'Chihiro (Sen)', 'Spirited Away (Studio Ghibli)', 'Courageous, gentle-hearted, persevering, quiet observer, and finding wonder in the hidden spirit world.', 'Courageous, gentle-hearted, persevering, quiet observer, and finding wonder in the hidden spirit world.', 'Chihiro represents the healing calm of acoustic and ambient sounds. Her quiet courage and mindful serenity align with listeners who seek sanctuary, clarity, and peace in their playlists.', 'assets/images/characters/chihiro.svg', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY))
ON DUPLICATE KEY UPDATE movie_name = VALUES(movie_name), movie_description = VALUES(movie_description), character_image_url = VALUES(character_image_url);
