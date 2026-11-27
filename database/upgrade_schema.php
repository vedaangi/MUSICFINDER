<?php
require_once __DIR__ . '/../config/db.php';

echo "=== Upgrading VARP Database Schema ===\n";

function addColumnIfNotExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows === 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "[SUCCESS] Added column `$column` to `$table`\n";
        } else {
            echo "[ERROR] Failed to add `$column` to `$table`: " . $conn->error . "\n";
        }
    } else {
        echo "[INFO] Column `$column` already exists in `$table`\n";
    }
}

// 1. Extend weekly_movies table
addColumnIfNotExists($conn, 'weekly_movies', 'movie_year', 'INT DEFAULT 2023');
addColumnIfNotExists($conn, 'weekly_movies', 'movie_poster_url', 'VARCHAR(500) DEFAULT NULL');
addColumnIfNotExists($conn, 'weekly_movies', 'movie_music_fit', 'TEXT DEFAULT NULL');
addColumnIfNotExists($conn, 'weekly_movies', 'character_show', 'VARCHAR(150) DEFAULT NULL');
addColumnIfNotExists($conn, 'weekly_movies', 'character_image_url', 'VARCHAR(500) DEFAULT NULL');
addColumnIfNotExists($conn, 'weekly_movies', 'character_traits', 'TEXT DEFAULT NULL');
addColumnIfNotExists($conn, 'weekly_movies', 'character_music_match', 'TEXT DEFAULT NULL');

// 2. Create payments table
$paymentsTableSql = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_id INT DEFAULT NULL,
    order_id VARCHAR(100) NOT NULL,
    gateway_payment_id VARCHAR(100) DEFAULT NULL,
    gateway_signature VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 99.00,
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    status ENUM('created', 'authorized', 'captured', 'failed', 'refunded') NOT NULL DEFAULT 'created',
    payment_method VARCHAR(50) DEFAULT 'razorpay',
    gateway_response TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_gateway_payment (gateway_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($paymentsTableSql)) {
    echo "[SUCCESS] Created or verified payments table\n";
} else {
    echo "[ERROR] Failed to create payments table: " . $conn->error . "\n";
}

// 3. Seed / Update the 7 weekly features with complete rich data
$features = [
    1 => [
        'movie_year' => 2009,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'The film features an iconic indie-rock soundtrack including The Smiths, Regina Spektor, and Simon & Garfunkel, perfectly capturing the bittersweet nostalgia and introspective melancholy of your favorite indie tracks.',
        'character_show' => '(500) Days of Summer',
        'character_image_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Independent, quirky, honest, passionate about vintage vinyl, and comfortable dancing to her own beat.',
        'character_music_match' => 'Summer embodies the free-spirited nonconformity of indie music lovers. Her deep connection to poetic lyrics and vintage tunes reflects your thoughtful, nostalgic listening habits.'
    ],
    2 => [
        'movie_year' => 2001,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'Driven by energetic, empowering early-2000s pop anthems like Hoku\'s "Perfect Day", this film radiates the unapologetic vibrancy, glamour, and upbeat tempo of your pop favorites.',
        'character_show' => 'Legally Blonde',
        'character_image_url' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Radiant, fiercely loyal, razor-sharp, unapologetically feminine, and relentlessly positive.',
        'character_music_match' => 'Elle channels the infectious energy of top pop charts—she proves that warmth, vibrant pink aesthetics, and undeniable intellect make the ultimate power move.'
    ],
    3 => [
        'movie_year' => 2000,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'A love letter to classic arena rock and backstage culture, pulsing with legendary riffs from Led Zeppelin, The Who, and David Bowie that match your high-energy rock library.',
        'character_show' => 'Almost Famous',
        'character_image_url' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Bohemian, magnetic, empathetic, and profoundly devoted to the spiritual magic of music.',
        'character_music_match' => 'Penny represents the beating heart of rock devotees: someone who believes music is not just entertainment, but a sanctuary of truth, freedom, and emotional intensity.'
    ],
    4 => [
        'movie_year' => 2018,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'Featuring a groundbreaking hip-hop score curated with Post Malone, Vince Staples, and Lil Wayne, the film synchronizes rhythmic 808 beats and modern urban swagger with soaring storytelling.',
        'character_show' => 'Spider-Man: Into the Spider-Verse',
        'character_image_url' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Creative, expressive, resilient, deeply grounded in family, and unafraid to carve out a unique personal identity.',
        'character_music_match' => 'Miles uses hip-hop lyrics and street art to discover who he is, mirroring your appreciation for lyricism, bass grooves, and bold self-expression.'
    ],
    5 => [
        'movie_year' => 2016,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'An evocative visual and sonic symphony of acoustic pianos, brass harmonies, and melancholic melodies that celebrate the artistry and romance of classic and contemporary jazz.',
        'character_show' => 'La La Land',
        'character_image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Devoted, idealistic, musically gifted, nostalgic, and fiercely committed to authentic craftsmanship.',
        'character_music_match' => 'Sebastian lives for timeless melodies and improvisational nuance, aligning with your love for soulful instrumentation, twilight chords, and expressive musicianship.'
    ],
    6 => [
        'movie_year' => 2010,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1508739773434-c26b3d09e071?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'Elevated by Daft Punk\'s iconic electronic-orchestral fusion, the film pulses with driving synthesizer basslines, neon arpeggios, and futuristic rhythm that fuel your electronic taste.',
        'character_show' => 'Tron: Legacy',
        'character_image_url' => 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Inquisitive, agile, fiercely protective, fascinated by the wider world, and technologically brilliant.',
        'character_music_match' => 'Quorra embodies sleek, high-tempo electronic power combined with intellectual curiosity, mirroring the energetic pulse of synth-wave and digital dance tracks.'
    ],
    7 => [
        'movie_year' => 1995,
        'movie_poster_url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80',
        'movie_music_fit' => 'A gentle, acoustic masterpiece centered around homemade Japanese translations of "Country Roads" and lyrical daydreams, matching your soothing, acoustic, and ambient music selections.',
        'character_show' => 'Whisper of the Heart',
        'character_image_url' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80',
        'character_traits' => 'Daydreamer, aspiring writer, observant, deeply thoughtful, and discovering her authentic inner voice.',
        'character_music_match' => 'Shizuku turns melodies into poetry and seeks wonder in everyday life, perfectly matching listeners who gravitate toward quiet acoustics, lo-fi beats, and reflective ballads.'
    ]
];

$updateStmt = $conn->prepare("UPDATE weekly_movies SET 
    movie_year = ?, 
    movie_poster_url = ?, 
    movie_music_fit = ?, 
    character_show = ?, 
    character_image_url = ?, 
    character_traits = ?, 
    character_music_match = ?
    WHERE weekly_id = ?");

foreach ($features as $id => $f) {
    $updateStmt->bind_param(
        'issssssi',
        $f['movie_year'],
        $f['movie_poster_url'],
        $f['movie_music_fit'],
        $f['character_show'],
        $f['character_image_url'],
        $f['character_traits'],
        $f['character_music_match'],
        $id
    );
    $updateStmt->execute();
    echo "[SUCCESS] Updated weekly feature #$id ({$f['character_show']})\n";
}
$updateStmt->close();

echo "=== Schema Upgrade Completed Successfully ===\n";
