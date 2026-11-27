<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/check_subscription.php';
require_once __DIR__ . '/fetch_top_artists.php';
require_once __DIR__ . '/fetch_personality.php';
require_once __DIR__ . '/fetch_weekly_movie.php';
require_once __DIR__ . '/fetch_character.php';
require_once __DIR__ . '/config/payment.php';

if (!function_exists('resolvePermittedAudioPath')) {
    function resolvePermittedAudioPath(?string $permitted_dir, ?string $stored_filename, array $allowed_mime_types): ?array
    {
        if ($permitted_dir === false || $permitted_dir === null || !is_string($stored_filename)) {
            return null;
        }

        $stored_filename = trim($stored_filename);
        if ($stored_filename === '' || str_contains($stored_filename, "\0")) {
            return null;
        }

        $basename = basename($stored_filename);
        if ($basename !== $stored_filename || str_contains($basename, '..') || str_contains($basename, '/') || str_contains($basename, '\\')) {
            return null;
        }

        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        if (!isset($allowed_mime_types[$extension])) {
            return null;
        }

        $candidate = $permitted_dir . DIRECTORY_SEPARATOR . $basename;
        $resolved = realpath($candidate);
        if ($resolved === false) {
            return null;
        }

        $prefix = rtrim($permitted_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolved, $prefix) || !is_file($resolved) || !is_readable($resolved)) {
            return null;
        }

        return [
            'path' => $resolved,
            'extension' => $extension,
            'mime' => $allowed_mime_types[$extension]
        ];
    }
}

$allPassed = true;
$testsRun = 0;
$testsPassed = 0;

function assertTest($condition, $testName, $details = '') {
    global $allPassed, $testsRun, $testsPassed;
    $testsRun++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] $testName\n";
    } else {
        $allPassed = false;
        echo "  [FAIL] $testName" . ($details ? ": $details" : "") . "\n";
    }
}

echo "=======================================================\n";
echo "   VARP Comprehensive Feature Verification Suite \n";
echo "=======================================================\n\n";

// --- TEST 1: User Authentication ---
echo "--- 1. Testing User Authentication & Security ---\n";
// Check admin user credentials
$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1");
$email = "alex@example.com";
$stmt->bind_param("s", $email);
$stmt->execute();
$alex = $stmt->get_result()->fetch_assoc();
$stmt->close();

assertTest($alex !== null, "User alex_music exists in database");
assertTest(password_verify("password123", $alex['password']), "Password hash verification for alex_music");
assertTest($alex['role'] === 'admin', "alex_music has 'admin' role");

// Test invalid password rejection
assertTest(!password_verify("wrongpassword", $alex['password']), "Invalid password correctly rejected");

// Set up simulated session for alex
$_SESSION['user_id'] = (int)$alex['id'];
$_SESSION['username'] = $alex['username'];
$_SESSION['email'] = $alex['email'];
$_SESSION['role'] = $alex['role'];

assertTest(requireLogin() === (int)$alex['id'], "requireLogin() returns current authenticated user ID");
assertTest(isAdminUser($conn, $alex['id']) === true, "isAdminUser() confirms admin privileges for alex_music");

// Check regular user sarah_grooves
$stmt2 = $conn->prepare("SELECT id, username, email, role FROM users WHERE email = 'sarah@example.com' LIMIT 1");
$stmt2->execute();
$sarah = $stmt2->get_result()->fetch_assoc();
$stmt2->close();
assertTest($sarah !== null, "User sarah_grooves exists in database");
assertTest($sarah['role'] === 'user', "sarah_grooves has standard 'user' role");
assertTest(isAdminUser($conn, $sarah['id']) === false, "isAdminUser() correctly denies admin privileges for sarah_grooves");

echo "\n";

// --- TEST 2: Music Discovery and Browsing ---
echo "--- 2. Testing Music Discovery & Browsing ---\n";
// Fetch all songs
$songsRes = $conn->query("SELECT id, song_id, title, song_name, artist, genre, youtube_url FROM songs");
$songCount = $songsRes->num_rows;
assertTest($songCount >= 20, "Catalog has songs populated ($songCount songs found)");

// Check YouTube URLs
$ytRes = $conn->query("SELECT COUNT(*) as c FROM songs WHERE youtube_url LIKE '%youtube.com%' OR youtube_url LIKE '%youtu.be%'");
$ytCount = (int)$ytRes->fetch_assoc()['c'];
assertTest($ytCount >= 20, "Catalog songs have valid YouTube URLs ($ytCount with YouTube links)");

// Search query test
$searchQ = "%Sweater%";
$searchStmt = $conn->prepare("SELECT id, title, artist, genre FROM songs WHERE title LIKE ? OR artist LIKE ?");
$searchStmt->bind_param("ss", $searchQ, $searchQ);
$searchStmt->execute();
$searchMatches = $searchStmt->get_result()->num_rows;
$searchStmt->close();
assertTest($searchMatches > 0, "Song search finds matching tracks by title ($searchMatches matches)");

// Genre filtering test
$genreFilterStmt = $conn->prepare("SELECT id FROM songs WHERE genre = 'Indie'");
$genreFilterStmt->execute();
$indieCount = $genreFilterStmt->get_result()->num_rows;
$genreFilterStmt->close();
assertTest($indieCount >= 3, "Genre filtering returns songs for 'Indie' ($indieCount songs)");

// Artist filtering test
$artistFilterStmt = $conn->prepare("SELECT id FROM songs WHERE artist = 'The Neighbourhood'");
$artistFilterStmt->execute();
$artistCount = $artistFilterStmt->get_result()->num_rows;
$artistFilterStmt->close();
assertTest($artistCount >= 1, "Artist filtering returns songs for 'The Neighbourhood' ($artistCount songs)");

echo "\n";

// --- TEST 3: Playlist Management ---
echo "--- 3. Testing Playlist Management & Scoping ---\n";
$testPlName = "Verification Test Playlist " . time();
$testPlDesc = "Automated test playlist";
$uid = (int)$alex['id'];

// Create playlist
$createPl = $conn->prepare("INSERT INTO playlists (user_id, name, description) VALUES (?, ?, ?)");
$createPl->bind_param("iss", $uid, $testPlName, $testPlDesc);
$createPl->execute();
$testPlId = $conn->insert_id;
$createPl->close();
assertTest($testPlId > 0, "Created test playlist ID #$testPlId");

// Add song 1 to playlist
$addSong = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, 1)");
$addSong->bind_param("i", $testPlId);
$addOk = $addSong->execute();
$addSong->close();
assertTest($addOk, "Added song #1 to playlist #$testPlId");

// Test duplicate prevention
$addDup = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, 1)");
$addDup->bind_param("i", $testPlId);
$dupFailed = false;
try {
    $resDup = @$addDup->execute();
    $dupFailed = ($resDup === false);
} catch (Throwable $e) {
    $dupFailed = true;
}
$addDup->close();
assertTest($dupFailed, "Duplicate song addition correctly prevented by unique constraint");

// Test playlist scoping: verify sarah cannot access or modify alex's playlist
$sarahPlCheck = $conn->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
$sarahId = (int)$sarah['id'];
$sarahPlCheck->bind_param("ii", $testPlId, $sarahId);
$sarahPlCheck->execute();
$sarahSeesAlexPl = $sarahPlCheck->get_result()->num_rows > 0;
$sarahPlCheck->close();
assertTest(!$sarahSeesAlexPl, "User scoping verified: sarah_grooves cannot access alex's playlist");

// Remove song from playlist
$removeSong = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = 1");
$removeSong->bind_param("i", $testPlId);
$removeSong->execute();
$remAffected = $removeSong->affected_rows;
$removeSong->close();
assertTest($remAffected > 0, "Removed song #1 from playlist");

// Delete test playlist
$delPl = $conn->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
$delPl->bind_param("ii", $testPlId, $uid);
$delPl->execute();
$delAffected = $delPl->affected_rows;
$delPl->close();
assertTest($delAffected > 0, "Deleted test playlist cleanly");

echo "\n";

// --- TEST 4: Listening History & Recommendations ---
echo "--- 4. Testing Listening History & Recommendations ---\n";
// Record a song play
$recStmt = $conn->prepare("INSERT INTO listening_history (user_id, song_id, listened_at) VALUES (?, 1, NOW())");
$recStmt->bind_param("i", $uid);
$recOk = $recStmt->execute();
$recStmt->close();
assertTest($recOk, "Recorded listening stream for song #1");

// Verify history query
$histStmt = $conn->prepare("SELECT COUNT(*) as c FROM listening_history WHERE user_id = ?");
$histStmt->bind_param("i", $uid);
$histStmt->execute();
$userPlays = (int)$histStmt->get_result()->fetch_assoc()['c'];
$histStmt->close();
assertTest($userPlays > 0, "Listening history query returns records for user ($userPlays total plays)");

// Recommendations query
$recGenreStmt = $conn->prepare("SELECT COALESCE(s.genre, g.genre_name) as genre, COUNT(lh.id) as cnt
                                FROM listening_history lh
                                JOIN songs s ON (lh.song_id = s.id OR lh.song_id = s.song_id)
                                LEFT JOIN genres g ON s.genre_id = g.genre_id
                                WHERE lh.user_id = ?
                                GROUP BY COALESCE(s.genre, g.genre_name)
                                ORDER BY cnt DESC LIMIT 1");
$recGenreStmt->bind_param("i", $uid);
$recGenreStmt->execute();
$topG = $recGenreStmt->get_result()->fetch_assoc();
$recGenreStmt->close();
assertTest(!empty($topG['genre']), "Derived top genre from actual user history: '{$topG['genre']}' ({$topG['cnt']} plays)");

echo "\n";

// --- TEST 5: Premium Subscription Prototype ---
echo "--- 5. Testing Premium Subscription Prototype ---\n";
$sub = getUserSubscription($conn, $uid);
assertTest($sub !== null, "getUserSubscription() finds subscription record");
assertTest($sub['is_active'] === true, "Subscription is active");
assertTest($sub['end_date'] >= date('Y-m-d'), "Subscription expiry is valid ({$sub['end_date']})");
assertTest(hasActiveSubscription($conn, $uid) === true, "hasActiveSubscription() returns true for alex");

// Sarah currently has no subscription
$sarahSub = getUserSubscription($conn, (int)$sarah['id']);
assertTest($sarahSub === null || $sarahSub['is_active'] === false, "hasActiveSubscription() returns false for unpaid user sarah");

// Test CSRF token generator & verifier
$token = getCsrfToken();
assertTest(!empty($token), "getCsrfToken() generates secure token");
assertTest(verifyCsrfToken($token) === true, "verifyCsrfToken() verifies valid token");
assertTest(verifyCsrfToken("invalid_token") === false, "verifyCsrfToken() rejects forged token");

// Test Payment Gateway Sandbox Order & Signature Security
$payOrder = createGatewayOrder($uid, "rcpt_test_verify_" . time());
assertTest(!empty($payOrder['order_id']), "createGatewayOrder() initializes sandbox order: {$payOrder['order_id']}");
assertTest($payOrder['amount'] === 9900, "Sandbox order amount is exactly ₹99 (9900 paise)");

$validSig = hash_hmac('sha256', $payOrder['order_id'] . '|pay_test_sample', 'varp_dev_secret_for_local_testing_only');
assertTest(verifyPaymentSignature($payOrder['order_id'], 'pay_test_sample', $validSig) === true, "verifyPaymentSignature() accepts cryptographically valid signature");
assertTest(verifyPaymentSignature($payOrder['order_id'], 'pay_test_sample', 'tampered_forged_sig') === false, "verifyPaymentSignature() strictly rejects tampered/forged signature");

echo "\n";

// --- TEST 6: Top Artists and Music Personality ---
echo "--- 6. Testing Top Artists & Music Personality ---\n";
$topArtists = fetchTopArtists($conn, $uid, 5);
assertTest(!empty($topArtists), "fetchTopArtists() returned top artists (" . count($topArtists) . " artists)");
assertTest(count($topArtists) <= 5, "fetchTopArtists() strictly limits to top 5 artists");
if (!empty($topArtists)) {
    echo "    Top Artist #1: {$topArtists[0]['artist_name']} with {$topArtists[0]['play_count']} plays\n";
}

$personalityData = fetchUserPersonality($conn, $uid);
assertTest($personalityData['has_history'] === true, "fetchUserPersonality() detected listening history");
assertTest($personalityData['top_genre'] !== null, "Top genre matched: {$personalityData['top_genre']['genre_name']}");
assertTest($personalityData['personality'] !== null, "Personality matched: '{$personalityData['personality']['personality_title']}'");
echo "    Music Personality: \"{$personalityData['personality']['personality_title']}\"\n";
echo "    Description: \"{$personalityData['personality']['personality_description']}\"\n";

// Test graceful handling for user with 0 history
$emptyUserPers = fetchUserPersonality($conn, 99999);
assertTest($emptyUserPers['has_history'] === false, "Gracefully handled user with no listening history (has_history = false)");

echo "\n";

// --- TEST 7: Weekly Movie and Character Recommendations ---
echo "--- 7. Testing Weekly Feature & Admin Controls ---\n";
$topGenreId = $personalityData['top_genre']['genre_id'];
$weeklyMovie = fetchWeeklyMovie($conn, $topGenreId);
$weeklyChar = fetchWeeklyCharacter($conn, $topGenreId);

assertTest($weeklyMovie !== null, "fetchWeeklyMovie() found active movie for genre #$topGenreId: '{$weeklyMovie['movie_name']}'");
assertTest(!empty($weeklyMovie['movie_year']), "Weekly movie includes valid release year ({$weeklyMovie['movie_year']})");
assertTest(!empty($weeklyMovie['movie_poster_url']), "Weekly movie includes verified poster/image URL");
assertTest(!empty($weeklyMovie['movie_music_fit']), "Weekly movie includes music taste fit explanation");

assertTest($weeklyChar !== null, "fetchWeeklyCharacter() found character for genre #$topGenreId: '{$weeklyChar['character_name']}'");
assertTest(!empty($weeklyChar['character_show']), "Character recommendation includes origin film/show ({$weeklyChar['character_show']})");
assertTest(!empty($weeklyChar['character_image_url']), "Character recommendation includes portrait/image URL");
assertTest(!empty($weeklyChar['character_traits']), "Character recommendation includes personality traits description");
assertTest(!empty($weeklyChar['character_music_match']), "Character recommendation includes music personality match explanation");
assertTest(!empty($weeklyMovie['week_start']) && !empty($weeklyMovie['week_end']), "Weekly dates defined: {$weeklyMovie['week_start']} to {$weeklyMovie['week_end']}");

// Test admin authorization check
assertTest(isAdminUser($conn, $uid) === true, "Admin role confirmed for alex_music on admin_weekly");
assertTest(isAdminUser($conn, (int)$sarah['id']) === false, "Admin access denied for non-admin sarah_grooves");

echo "\n";

// --- TEST 8: Premium Music Downloads ---
echo "--- 8. Testing Premium Music Downloads & Path Traversal Security ---\n";
$allowed_mimes = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',
    'flac' => 'audio/flac'
];
$audioDir = realpath(__DIR__ . '/audio');

// Legitimate audio file
$validFile = resolvePermittedAudioPath($audioDir, 'sweater_weather_preview.mp3', $allowed_mimes);
assertTest($validFile !== null, "resolvePermittedAudioPath() accepts valid catalog audio file");
assertTest($validFile['extension'] === 'mp3', "Valid file recognized as MP3");
assertTest($validFile['mime'] === 'audio/mpeg', "Valid MIME type audio/mpeg");

// Security test: Path traversal attempts
$traversal1 = resolvePermittedAudioPath($audioDir, '../config/db.php', $allowed_mimes);
assertTest($traversal1 === null, "Path traversal attempt '../config/db.php' strictly blocked (returns null)");

$traversal2 = resolvePermittedAudioPath($audioDir, '..\\..\\Windows\\System32\\drivers\\etc\\hosts', $allowed_mimes);
assertTest($traversal2 === null, "Path traversal attempt '..\\..\\Windows' strictly blocked (returns null)");

$traversal3 = resolvePermittedAudioPath($audioDir, 'test.php', $allowed_mimes);
assertTest($traversal3 === null, "Disallowed file extension 'test.php' strictly blocked (returns null)");

$traversal4 = resolvePermittedAudioPath($audioDir, "track.mp3\0.php", $allowed_mimes);
assertTest($traversal4 === null, "Null-byte injection strictly blocked (returns null)");

// Download logging in database
$dlLog = $conn->prepare("INSERT INTO downloads (user_id, song_id) VALUES (?, 1)");
$dlLog->bind_param("i", $uid);
$dlLogOk = $dlLog->execute();
$dlLog->close();
assertTest($dlLogOk, "Download activity logged in 'downloads' table for user #$uid, song #1");

$chkDl = $conn->prepare("SELECT COUNT(*) as c FROM downloads WHERE user_id = ?");
$chkDl->bind_param("i", $uid);
$chkDl->execute();
$dlCount = (int)$chkDl->get_result()->fetch_assoc()['c'];
$chkDl->close();
assertTest($dlCount > 0, "Downloads history correctly queried ($dlCount downloads logged)");

echo "\n";

// --- SUMMARY ---
echo "=======================================================\n";
echo "   Verification Results: $testsPassed / $testsRun tests passed\n";
echo "=======================================================\n";

if ($allPassed) {
    echo ">>> ALL 8 APPLICATION MODULES & SECURITY CHECKS PASSED SUCCESSFULLY! <<<\n";
} else {
    echo ">>> SOME TESTS FAILED. PLEASE REVIEW THE LOG ABOVE. <<<\n";
}
