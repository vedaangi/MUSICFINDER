<?php
session_start();
require_once 'config/db.php';
$is_logged_in = isset($_SESSION["user_id"]);
$username = $_SESSION["username"] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Music Finder</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav style="margin-bottom: 25px; padding: 12px; background: white; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); display: inline-block;">
        <?php if ($is_logged_in): ?>
            <span>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>!</span> |
            <a href="dashboard.php">Dashboard</a> |
            <a href="playlists.php">Playlists</a> |
            <a href="recommendations.php">Recommendations</a> |
            <a href="history.php">History</a> |
            <a href="profile.php">Profile</a> |
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a> |
            <a href="register.php">Register</a> |
            <a href="playlists.php">Playlists</a>
        <?php endif; ?>
    </nav>

    <h1>🎵 Music Finder</h1>
    <p>Discover your favourite music!</p>

    <div class="search-box">
        <input
            type="text"
            id="searchInput"
            placeholder="Search songs or artists..."
        >
        <button onclick="searchSongs()">Search</button>
        <button onclick="browseAllSongs()">Browse All Songs</button>
    </div>

    <!-- Genre Filter -->
    <div class="genre-filter" style="margin: 15px 0;">
        <label for="genreFilter">Filter by Genre: </label>
        <select id="genreFilter" onchange="filterSongs()">
            <option value="">All Genres</option>
            <option value="Pop">Pop</option>
            <option value="Rock">Rock</option>
            <option value="Hard Rock">Hard Rock</option>
            <option value="Hip-Hop">Hip-Hop</option>
            <option value="Classical">Classical</option>
            <option value="Jazz">Jazz</option>
            <option value="Electronic">Electronic</option>
            <option value="R&B">R&B</option>
            <option value="Indie">Indie</option>
            <option value="Country">Country</option>
            <option value="Bollywood">Bollywood</option>
            <option value="Unknown">Unknown</option>
        </select>
    </div>

    <!-- Artist Filter -->
    <div class="artist-filter" style="margin: 15px 0;">
        <label for="artistFilter">Filter by Artist: </label>
        <select id="artistFilter" onchange="filterArtists()">
            <option value="">Select an Artist</option>
        </select>
    </div>

    <div id="results"></div>

    <script src="js/script.js"></script>

</body>
</html>
