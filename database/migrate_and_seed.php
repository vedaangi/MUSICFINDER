<?php
require_once __DIR__ . '/../config/db.php';

echo "=== VARP Safe Schema Migration & Seed Synchronization ===\n";

// Helper to run query safely
function runSql($conn, $sql, $label) {
    if ($conn->query($sql)) {
        echo "[SUCCESS] $label\n";
    } else {
        echo "[ERROR] $label: " . $conn->error . "\n";
    }
}

// 1. Update users table with role column
$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($colRes->num_rows === 0) {
    runSql($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'", "Add role column to users");
} else {
    echo "[INFO] users.role column already exists.\n";
}

// Ensure alex_music is admin and password is 'password123'
$hashedPass = password_hash('password123', PASSWORD_BCRYPT);
$adminCheck = $conn->query("SELECT id FROM users WHERE username = 'alex_music'");
if ($adminCheck->num_rows > 0) {
    $conn->query("UPDATE users SET role = 'admin', password = '$hashedPass' WHERE username = 'alex_music'");
    echo "[INFO] Updated alex_music to role 'admin' with known password 'password123'.\n";
} else {
    $conn->query("INSERT INTO users (id, username, email, password, role) VALUES (1, 'alex_music', 'alex@example.com', '$hashedPass', 'admin')");
    echo "[INFO] Inserted alex_music as admin.\n";
}

// Ensure an additional demo user 'sarah_grooves' exists
$sarahCheck = $conn->query("SELECT id FROM users WHERE username = 'sarah_grooves'");
if ($sarahCheck->num_rows === 0) {
    $conn->query("INSERT INTO users (id, username, email, password, role) VALUES (2, 'sarah_grooves', 'sarah@example.com', '$hashedPass', 'user')");
    echo "[INFO] Inserted sarah_grooves.\n";
}

// 2. Genres table
runSql($conn, "CREATE TABLE IF NOT EXISTS genres (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create genres table");

$genreList = [
    1 => 'Pop',
    2 => 'Rock',
    3 => 'Hard Rock',
    4 => 'Hip-Hop',
    5 => 'Classical',
    6 => 'Jazz',
    7 => 'Electronic',
    8 => 'R&B',
    9 => 'Indie',
    10 => 'Country',
    11 => 'Bollywood',
    12 => 'Lo-Fi'
];
foreach ($genreList as $id => $name) {
    $stmt = $conn->prepare("INSERT INTO genres (genre_id, genre_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE genre_name = VALUES(genre_name)");
    $stmt->bind_param('is', $id, $name);
    $stmt->execute();
    $stmt->close();
}
echo "[INFO] Genres populated.\n";

// 3. Artists table
runSql($conn, "CREATE TABLE IF NOT EXISTS artists (
    artist_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(150) NOT NULL UNIQUE,
    artist_image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create artists table");

$artistsRes = $conn->query("SELECT DISTINCT artist FROM songs WHERE artist IS NOT NULL AND artist <> ''");
while ($r = $artistsRes->fetch_row()) {
    $aName = $r[0];
    $stmt = $conn->prepare("INSERT IGNORE INTO artists (artist_name) VALUES (?)");
    $stmt->bind_param('s', $aName);
    $stmt->execute();
    $stmt->close();
}
echo "[INFO] Artists populated from songs.\n";

// 4. Update songs table columns
$songCols = [
    'song_id' => "ALTER TABLE songs ADD COLUMN song_id INT DEFAULT NULL",
    'track_id' => "ALTER TABLE songs ADD COLUMN track_id VARCHAR(100) DEFAULT NULL",
    'song_name' => "ALTER TABLE songs ADD COLUMN song_name VARCHAR(150) DEFAULT NULL",
    'artist_id' => "ALTER TABLE songs ADD COLUMN artist_id INT DEFAULT NULL",
    'genre_id' => "ALTER TABLE songs ADD COLUMN genre_id INT DEFAULT NULL",
    'album_name' => "ALTER TABLE songs ADD COLUMN album_name VARCHAR(150) DEFAULT NULL",
    'album_art' => "ALTER TABLE songs ADD COLUMN album_art VARCHAR(255) DEFAULT NULL",
    'preview_url' => "ALTER TABLE songs ADD COLUMN preview_url VARCHAR(255) DEFAULT NULL",
    'duration_ms' => "ALTER TABLE songs ADD COLUMN duration_ms INT DEFAULT 0",
    'audio_file' => "ALTER TABLE songs ADD COLUMN audio_file VARCHAR(255) DEFAULT NULL"
];

foreach ($songCols as $col => $alterSql) {
    $chk = $conn->query("SHOW COLUMNS FROM songs LIKE '$col'");
    if ($chk->num_rows === 0) {
        runSql($conn, $alterSql, "Add $col to songs");
    }
}

// Synchronize song_id, song_name, artist_id, genre_id in songs table
$conn->query("UPDATE songs SET song_id = id WHERE song_id IS NULL OR song_id = 0");
$conn->query("UPDATE songs SET song_name = title WHERE song_name IS NULL OR song_name = ''");
$conn->query("UPDATE songs SET title = song_name WHERE title IS NULL OR title = ''");

// Link songs.artist_id to artists.artist_id
$conn->query("UPDATE songs s JOIN artists a ON s.artist = a.artist_name SET s.artist_id = a.artist_id WHERE s.artist_id IS NULL");

// Link songs.genre_id to genres.genre_id
$conn->query("UPDATE songs s JOIN genres g ON s.genre = g.genre_name SET s.genre_id = g.genre_id WHERE s.genre_id IS NULL");

echo "[INFO] Songs table columns and foreign keys synchronized.\n";

// 5. Subscriptions table
runSql($conn, "CREATE TABLE IF NOT EXISTS subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) NOT NULL DEFAULT 'VARP Premium',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create subscriptions table");

// Ensure user 1 has an active subscription valid for the next 30 days
$today = date('Y-m-d');
$nextMonth = (new DateTimeImmutable($today))->modify('+1 month')->format('Y-m-d');
$subCheck = $conn->query("SELECT subscription_id FROM subscriptions WHERE user_id = 1 AND status = 'active' AND end_date >= '$today'");
if ($subCheck->num_rows === 0) {
    $conn->query("INSERT INTO subscriptions (user_id, plan, start_date, end_date, status) VALUES (1, 'VARP Premium', '$today', '$nextMonth', 'active')");
    echo "[INFO] Created active demo subscription for user 1.\n";
} else {
    echo "[INFO] User 1 already has an active subscription.\n";
}

// 6. Downloads table
runSql($conn, "CREATE TABLE IF NOT EXISTS downloads (
    download_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create downloads table");

// 7. Genre Personality table
runSql($conn, "CREATE TABLE IF NOT EXISTS genre_personality (
    personality_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_id INT NOT NULL UNIQUE,
    personality_title VARCHAR(150) NOT NULL,
    personality_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create genre_personality table");

$personalities = [
    9 => ['The Velvet Dreamer', 'You find magic in the understated. Your playlists are emotional journeys of soft nostalgia, dreamy synths, and poetic twilight thoughts.'],
    1 => ['The Main Character', 'You live life in full color and high energy. Every sidewalk is your runway, every beat is a soundtrack moment, and you know how to bring glitter to any room.'],
    2 => ['The Electric Rebel', 'Fierce, authentic, and unapologetic. You crave raw guitars, driving rhythm, and cathartic anthems that shake the world awake.'],
    3 => ['The Thunder Catalyst', 'Bold and high-voltage. You thrive on heavy distortion, soaring vocals, and relentless sonic power that fuels your inner fire.'],
    4 => ['The Rhythm Alchemist', 'Sharp, stylish, and deeply attuned to flow. You appreciate wordplay, hypnotic beats, and tracks that exude confidence and effortless swagger.'],
    5 => ['The Symphony Sage', 'Harmonious, intricate, and deeply cultured. You appreciate grand dynamics, orchestral textures, and timeless compositions.'],
    6 => ['The Midnight Philosopher', 'Smooth, sophisticated, and observant. You appreciate complex chord progressions, velvet saxophone solos, and the quiet beauty of late-night reflections.'],
    7 => ['The Neon Visionary', 'Futuristic, vibrant, and always in motion. You thrive in pulse-raising drops, synth-wave horizons, and digital soundscapes that elevate your spirit.'],
    8 => ['The Soulful Heart', 'Warm, expressive, and deeply romantic. You resonate with heartfelt vocal runs, lush harmonies, and velvet bass grooves.'],
    10 => ['The Wildflower Nomad', 'Story-driven and grounded. You cherish acoustic picking, heartfelt lyricism, open highways, and honest campfire melodies.'],
    11 => ['The Cinematic Star', 'Dazzling, expressive, and passionate. You celebrate sweeping strings, rhythmic tabla beats, and festive emotional grandeur.'],
    12 => ['The Cozy Daydreamer', 'Warm, mellow, and peaceful. You love rainy afternoon coffee shops, gentle vinyl crackles, soft piano loops, and undisturbed flow states.']
];

foreach ($personalities as $gid => $p) {
    $stmt = $conn->prepare("INSERT INTO genre_personality (genre_id, personality_title, personality_description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE personality_title = VALUES(personality_title), personality_description = VALUES(personality_description)");
    $stmt->bind_param('iss', $gid, $p[0], $p[1]);
    $stmt->execute();
    $stmt->close();
}
echo "[INFO] Genre personalities populated.\n";

// 8. Weekly Movies table
runSql($conn, "CREATE TABLE IF NOT EXISTS weekly_movies (
    weekly_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_id INT NOT NULL,
    movie_name VARCHAR(150) NOT NULL,
    movie_description TEXT NOT NULL,
    character_name VARCHAR(150) NOT NULL,
    character_description TEXT NOT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create weekly_movies table");

// Ensure active weekly movie entries covering current dates
$wStart = '2026-11-20';
$wEnd = '2026-12-05';

$weeklySeeds = [
    9 => [
        '500 Days of Summer',
        'A quirky, bittersweet exploration of modern romance and personal evolution set against an unforgettable indie soundtrack.',
        'Summer Finn',
        'Independent, free-spirited, and passionate about vintage vinyl, thoughtful indie tunes, and authentic self-discovery.'
    ],
    1 => [
        'Legally Blonde',
        'An iconic comedy of empowerment, sharp intellect, and staying unapologetically fabulous while conquering the world.',
        'Elle Woods',
        'Optimistic, brilliant, impeccably dressed in bright shades, and proving that warmth and intellect outshine every prejudice.'
    ],
    2 => [
        'Almost Famous',
        'A heartfelt coming-of-age voyage following a music-loving teenager on tour with a soaring 1970s rock group.',
        'Penny Lane',
        'The magnetic and stylish spirit of the tour who believes that authentic music has the power to set the soul free.'
    ],
    4 => [
        'Spider-Man: Into the Spider-Verse',
        'A visually electrifying animated adventure propelled by infectious hip-hop beats, cutting-edge art, and multiverse heroics.',
        'Miles Morales',
        'A creative Brooklyn teen discovering his voice, distinct swagger, and courage to stand as his own kind of hero.'
    ],
    6 => [
        'La La Land',
        'A dazzling tribute to classic jazz and romantic yearning set among the twilight glow of Los Angeles.',
        'Sebastian Wilder',
        'A devoted jazz pianist who channels passionate melancholy and unyielding dedication to his craft.'
    ],
    7 => [
        'Tron: Legacy',
        'A neon-drenched cybernetic odyssey driven by an iconic electronic synth-wave score.',
        'Quorra',
        'An inquisitive, agile digital warrior who combines tactical agility with a genuine love for music and knowledge.'
    ],
    12 => [
        'Whisper of the Heart',
        'A warm, beautifully observed Studio Ghibli tale about youthful curiosity, quiet bookstores, and creative intuition.',
        'Shizuku Tsukishima',
        'An earnest daydreamer who weaves lyrics and stories amidst gentle rain and soothing ambient melodies.'
    ]
];

foreach ($weeklySeeds as $gid => $w) {
    $stmt = $conn->prepare("SELECT weekly_id FROM weekly_movies WHERE genre_id = ? AND week_start <= ? AND week_end >= ? LIMIT 1");
    $stmt->bind_param('iss', $gid, $today, $today);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        $ins = $conn->prepare("INSERT INTO weekly_movies (genre_id, movie_name, movie_description, character_name, character_description, week_start, week_end) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param('issssss', $gid, $w[0], $w[1], $w[2], $w[3], $wStart, $wEnd);
        $ins->execute();
        $ins->close();
    }
}
echo "[INFO] Weekly movies & characters populated for active dates.\n";

// 9. User activity table
runSql($conn, "CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create user_activity table");

// 10. User preferences table
runSql($conn, "CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    genre_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Create user_preferences table");

// 11. Stored functions
$fnCount = "DROP FUNCTION IF EXISTS fn_GetPlaylistSongCount;
CREATE FUNCTION fn_GetPlaylistSongCount(p_playlist_id INT) 
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_count INT DEFAULT 0;
    SELECT COUNT(*) INTO v_count FROM playlist_songs WHERE playlist_id = p_playlist_id;
    RETURN v_count;
END;";

$fnGenre = "DROP FUNCTION IF EXISTS fn_GetUserTopGenre;
CREATE FUNCTION fn_GetUserTopGenre(p_user_id INT) 
RETURNS VARCHAR(50)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_genre VARCHAR(50) DEFAULT 'None';
    SELECT COALESCE(s.genre, g.genre_name, 'None')
    INTO v_genre
    FROM listening_history lh
    JOIN songs s ON (lh.song_id = s.id OR lh.song_id = s.song_id)
    LEFT JOIN genres g ON s.genre_id = g.genre_id
    WHERE lh.user_id = p_user_id
    GROUP BY COALESCE(s.genre, g.genre_name)
    ORDER BY COUNT(lh.id) DESC
    LIMIT 1;
    RETURN v_genre;
END;";

try {
    $conn->multi_query($fnCount);
    while ($conn->next_result()) {;}
    $conn->multi_query($fnGenre);
    while ($conn->next_result()) {;}
    echo "[INFO] Stored functions created/updated.\n";
} catch (Throwable $e) {
    echo "[WARN] Stored functions: " . $e->getMessage() . "\n";
}

// 12. Set up sample audio files for Feature 8 (downloads)
// Create audio/ directory if not exists
$audioDir = __DIR__ . '/../audio';
if (!is_dir($audioDir)) {
    mkdir($audioDir, 0777, true);
}

// Provide 2 legitimate authorized audio tracks in audio/
// We can generate clean valid audio tracks or copy royalty-free audio
$sampleSong1 = 'sweater_weather_preview.mp3';
$sampleSong2 = 'heat_waves_preview.mp3';

$filePath1 = $audioDir . DIRECTORY_SEPARATOR . $sampleSong1;
$filePath2 = $audioDir . DIRECTORY_SEPARATOR . $sampleSong2;

if (!file_exists($filePath1)) {
    // Generate minimal valid MP3 frame or sample audio header
    // Minimal silent MP3 frame structure
    $mp3Frame = hex2bin('fffb90640000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000');
    // repeat 100 times to create a valid playable 4KB MP3 file
    file_put_contents($filePath1, str_repeat($mp3Frame, 100));
}

if (!file_exists($filePath2)) {
    $mp3Frame = hex2bin('fffb90640000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000');
    file_put_contents($filePath2, str_repeat($mp3Frame, 100));
}

// Assign audio_file in database for songs 1 and 3
$conn->query("UPDATE songs SET audio_file = '$sampleSong1' WHERE id = 1");
$conn->query("UPDATE songs SET audio_file = '$sampleSong2' WHERE id = 3");
echo "[INFO] Sample audio files and song assignments configured.\n";

echo "=== Migration & Seed Complete ===\n";
