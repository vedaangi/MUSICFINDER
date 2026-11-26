
async function searchSongs() {

    const query = document.getElementById("searchInput").value.trim();

    if (query === "") {
        alert("Please enter a song or artist name!");
        return;
    }

    const resultsDiv = document.getElementById("results");

    resultsDiv.innerHTML = "<p>Searching...</p>";

    try {

        const response = await fetch(
            "api/itunes_search.php?q=" + encodeURIComponent(query)
        );

        const data = await response.json();

        resultsDiv.innerHTML = "";

        if (!data.results || data.results.length === 0) {
            resultsDiv.innerHTML = "<p>No songs found!</p>";
            return;
        }

        data.results.forEach(song => {

            const card = document.createElement("div");

            card.className = "song-card";

            card.innerHTML = `
                <img src="${song.artworkUrl100}" alt="Album cover">

                <h3>${song.trackName}</h3>

                <p>${song.artistName}</p>

                <p>${song.collectionName || "Unknown Album"}</p>

                <button class="save-button">Add to Music Finder</button>
            `;

            const saveButton = card.querySelector(".save-button");

            saveButton.addEventListener("click", () => saveSong(song, saveButton));

            if (song.previewUrl) {
                const audio = document.createElement("audio");
                audio.controls = true;
                audio.src = song.previewUrl;
                card.appendChild(audio);
            }

            resultsDiv.appendChild(card);

        });

    } catch (error) {

        resultsDiv.innerHTML = "<p>Something went wrong. Please try again.</p>";

        console.error(error);
    }
}


async function saveSong(song, button) {

    const formData = new FormData();

    formData.append("track_id", song.trackId);
    formData.append("song_name", song.trackName);
    formData.append("artist_name", song.artistName);
    formData.append("album_name", song.collectionName || "");
    formData.append("album_art", song.artworkUrl100 || "");
    formData.append("preview_url", song.previewUrl || "");
    formData.append("duration_ms", song.trackTimeMillis || 0);
    formData.append("genre_name", song.primaryGenreName || "Unknown");
    button.disabled = true;
    button.textContent = "Saving...";

    try {

        const response = await fetch("api/add_song.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        alert(data.message || data.error);

        button.textContent = data.message === "Song saved successfully!"
            ? "Added!"
            : "Add to Music Finder";

    } catch (error) {

        alert("Could not save song!");

        button.textContent = "Add to Music Finder";

    }

    button.disabled = false;
}



async function filterSongs() {

    const genreName = document.getElementById("genreFilter").value;
    const resultsDiv = document.getElementById("results");

    if (genreName === "") {
        resultsDiv.innerHTML = "<p>Select a genre to filter songs.</p>";
        return;
    }

    resultsDiv.innerHTML = "<p>Loading songs...</p>";

    try {

        const genreResponse = await fetch("api/fetch_genres.php");
        const genres = await genreResponse.json();

        const selectedGenre = genres.find(
            genre => genre.genre_name === genreName
        );

        if (!selectedGenre) {
            resultsDiv.innerHTML = "<p>Genre not found!</p>";
            return;
        }

        const response = await fetch(
            "api/filter_genre.php?genre_id=" + selectedGenre.genre_id
        );

        const songs = await response.json();

        resultsDiv.innerHTML = "";

        if (!Array.isArray(songs) || songs.length === 0) {
            resultsDiv.innerHTML = "<p>No songs found in this genre!</p>";
            return;
        }

        songs.forEach(song => {

            const card = document.createElement("div");
            card.className = "song-card";

            card.innerHTML = `
                <img src="${song.album_art || ''}" alt="Album Art">

                <h3>${song.song_name}</h3>

                <p>${song.artist_name || "Unknown Artist"}</p>
                <p>${song.album_name || "Unknown Album"}</p>
                <p>Genre: ${song.genre_name || "Unknown"}</p>

                ${song.youtube_url ? `
                    <p>
                        <a href="${song.youtube_url}" target="_blank" rel="noopener noreferrer">
                            Watch on YouTube
                        </a>
                    </p>
                ` : ""}

                <button class="edit-button">Edit Song</button>
                <button class="delete-button">Delete Song</button>
            `;

            card.querySelector(".edit-button").addEventListener(
                "click", () => editSong(song)
            );

            card.querySelector(".delete-button").addEventListener(
                "click", () => deleteSong(song)
            );

            resultsDiv.appendChild(card);

        });

    } catch (error) {

        resultsDiv.innerHTML = "<p>Could not filter songs. Please try again.</p>";
        console.error(error);

    }

}


// Load artists into the dropdown
async function loadArtists() {

    const response = await fetch("api/fetch_artists.php");
    const artists = await response.json();

    const dropdown = document.getElementById("artistFilter");

    artists.forEach(artist => {

        const option = document.createElement("option");

        option.value = artist.artist_id;
        option.textContent = artist.artist_name + " (" + artist.total_songs + " songs)";

        dropdown.appendChild(option);

    });
}

// Run when the page loads
document.addEventListener("DOMContentLoaded", loadArtists);




async function filterArtists() {

    const artistId = document.getElementById("artistFilter").value;
    const resultsDiv = document.getElementById("results");

    if (artistId === "") {
        resultsDiv.innerHTML = "";
        return;
    }

    const response = await fetch("api/filter_artist.php?artist_id=" + artistId);
    const songs = await response.json();

    resultsDiv.innerHTML = "";

    if (songs.length === 0) {
        resultsDiv.innerHTML = "<p>No songs found for this artist.</p>";
        return;
    }

    songs.forEach(song => {

        const card = document.createElement("div");
        card.className = "song-card";

        card.innerHTML = `
            <img src="${song.album_art || ''}" alt="Album Art">

            <h3>${song.song_name}</h3>

            <p>Artist: ${song.artist_name || "Unknown Artist"}</p>
            <p>Album: ${song.album_name || "Unknown"}</p>
            <p>Genre: ${song.genre_name || "Unknown"}</p>

            ${song.preview_url ? `
                <audio controls>
                    <source src="${song.preview_url}" type="audio/mpeg">
                </audio>
            ` : ""}

            ${song.youtube_url ? `
                <p>
                    <a href="${song.youtube_url}" target="_blank" rel="noopener noreferrer">
                        Watch on YouTube
                    </a>
                </p>
            ` : ""}

            <button class="edit-button">Edit Song</button>
            <button class="delete-button">Delete Song</button>
        `;

        card.querySelector(".edit-button").addEventListener(
            "click", () => editSong(song)
        );

        card.querySelector(".delete-button").addEventListener(
            "click", () => deleteSong(song)
        );

        resultsDiv.appendChild(card);

    });

}

async function browseAllSongs() {

    const resultsDiv = document.getElementById("results");

    const response = await fetch("api/fetch_songs.php");

    const songs = await response.json();

    resultsDiv.innerHTML = "";

    if (songs.length === 0) {
        resultsDiv.innerHTML = "<p>No songs have been added yet.</p>";
        return;
    }

    songs.forEach(song => {

        const card = document.createElement("div");
        card.className = "song-card";

        card.innerHTML = `
            <img src="${song.album_art || ''}" alt="Album Art">

            <h3>${song.song_name}</h3>

            <p>Artist: ${song.artist_name || "Unknown Artist"}</p>

            <p>Album: ${song.album_name || "Unknown"}</p>

            <p>Genre: ${song.genre_name || "Unknown"}</p>

            ${song.preview_url ? `
                <audio controls>
                    <source src="${song.preview_url}" type="audio/mpeg">
                </audio>
            ` : ""}

            ${song.youtube_url ? `
                <p>
                    <a href="${song.youtube_url}" target="_blank" rel="noopener noreferrer">
                        Watch on YouTube
                    </a>
                </p>
            ` : ""}

            <button class="edit-button">Edit Song</button>
            <button class="delete-button">Delete Song</button>
        `;

        card.querySelector(".edit-button").addEventListener(
            "click",
            () => editSong(song)
        );

        card.querySelector(".delete-button").addEventListener(
            "click",
            () => deleteSong(song)
        );

        resultsDiv.appendChild(card);

    });

}


async function editSong(song) {

    const songName = prompt("Enter song name:", song.song_name);
    if (songName === null) return;

    const artistName = prompt("Enter artist name:", song.artist_name);
    if (artistName === null) return;

    const albumName = prompt("Enter album name:", song.album_name || "");
    if (albumName === null) return;

    const genreName = prompt("Enter genre:", song.genre_name || "");
    if (genreName === null) return;

    if (songName.trim() === "" || artistName.trim() === "") {
        alert("Song name and artist name cannot be empty.");
        return;
    }

    const formData = new FormData();

    formData.append("song_id", song.song_id);
    formData.append("song_name", songName.trim());
    formData.append("artist_name", artistName.trim());
    formData.append("album_name", albumName.trim());
    formData.append("genre_name", genreName.trim());

    try {

        const response = await fetch("api/edit_song.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.success);
            browseAllSongs();
        } else {
            alert(data.error || "Could not update song.");
        }

    } catch (error) {
        alert("Something went wrong while editing the song.");
        console.error(error);
    }
}


async function deleteSong(song) {

    const confirmDelete = confirm(
        "Are you sure you want to delete '" + song.song_name + "'?"
    );

    if (!confirmDelete) {
        return;
    }

    const formData = new FormData();

    formData.append("song_id", song.song_id);

    try {

        const response = await fetch("api/delete_song.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {

            alert(data.success);

            browseAllSongs();

        } else {

            alert(data.error || "Could not delete song.");

        }

    } catch (error) {

        alert("Something went wrong while deleting the song.");

        console.error(error);

    }

}
