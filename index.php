
<!DOCTYPE html>
<html>
<head>
    <title>Music Finder</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

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
    <div class="genre-filter">
        <label for="genreFilter">Filter by Genre:</label>

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
<div class="artist-filter">

    <label for="artistFilter">Filter by Artist:</label>

    <select id="artistFilter" onchange="filterArtists()">
        <option value="">Select an Artist</option>
    </select>

</div>


    <div id="results"></div>

    <script src="js/script.js"></script>

</body>
</html>
