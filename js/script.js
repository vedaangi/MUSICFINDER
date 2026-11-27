/* ==========================================================================
   VARP - Discovery, Search, Player & Playlist Integration (Girly Pop)
   ========================================================================== */

let selectedSongForPlaylist = null; // Can be an existing local ID or a remote song object

// Escape HTML utility to prevent XSS
function escapeHtml(text) {
    if (!text) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Generate YouTube link info with accessible label and fallback search
function getYouTubeLinkInfo(songName, artistName, directYoutubeUrl) {
    const title = songName || "Untitled";
    const artist = artistName || "Unknown Artist";
    const savedUrl = (directYoutubeUrl || "").trim();

    const isDirect = savedUrl && (savedUrl.includes("youtube.com/watch") || savedUrl.includes("youtu.be/"));

    if (isDirect) {
        return {
            url: savedUrl,
            tooltip: `Watch "${title}" by ${artist} on YouTube`,
            label: "Watch on YouTube",
            isDirect: true
        };
    } else if (savedUrl && savedUrl.includes("youtube.com/results")) {
        return {
            url: savedUrl,
            tooltip: `Find "${title}" by ${artist} on YouTube`,
            label: "Find on YouTube",
            isDirect: false
        };
    } else {
        const query = encodeURIComponent((title + " " + artist).trim() + " official audio");
        return {
            url: `https://www.youtube.com/results?search_query=${query}`,
            tooltip: `Find "${title}" by ${artist} on YouTube`,
            label: "Find on YouTube",
            isDirect: false
        };
    }
}

// Extract YouTube video ID from URL
function extractYouTubeVideoId(url) {
    if (!url) return null;
    const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
    return match ? match[1] : null;
}

// Graceful fallback handler for song artwork
function handleSongArtError(img, secondaryUrl) {
    if (secondaryUrl && img.src !== secondaryUrl && !img.dataset.triedSecondary) {
        img.dataset.triedSecondary = "true";
        img.src = secondaryUrl;
        return;
    }
    img.onerror = null;
    img.src = "assets/images/placeholder_song.svg";
    img.classList.add("is-fallback");
    img.alt = "Artwork unavailable";
}

// 1. Unified Global Search (Searches both Local Catalog & Worldwide iTunes Catalog)
async function searchGlobalMusic() {
    const query = document.getElementById("searchInput").value.trim();
    if (!query) {
        browseAllSongs();
        return;
    }

    const resultsDiv = document.getElementById("results");
    const countElem = document.getElementById("resultsCount");
    if (countElem) countElem.innerText = "Searching...";

    resultsDiv.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px 20px; color: var(--text-secondary);">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 36px; color: var(--forest-200); margin-bottom: 16px;"></i>
            <h3 style="color: #fff; margin: 0 0 8px 0;">Searching Worldwide Catalog</h3>
            <p style="margin: 0;">Exploring millions of tracks for "${escapeHtml(query)}"...</p>
        </div>
    `;

    try {
        const [localRes, remoteRes] = await Promise.allSettled([
            fetch("api/search_songs.php?q=" + encodeURIComponent(query)).then(r => r.json()),
            fetch("api/itunes_search.php?q=" + encodeURIComponent(query) + "&limit=35").then(r => r.json())
        ]);

        const localSongs = (localRes.status === "fulfilled" && Array.isArray(localRes.value)) ? localRes.value : [];
        const remoteData = (remoteRes.status === "fulfilled" && remoteRes.value && Array.isArray(remoteRes.value.results)) 
            ? remoteRes.value.results 
            : [];

        // Build deduplicated unified list
        const unifiedList = [];
        const seenKeys = new Set();

        // Add local songs first
        localSongs.forEach(s => {
            const songName = s.song_name || s.title || "";
            const artistName = s.artist_name || s.artist || "";
            const key = (songName + "|" + artistName).toLowerCase().replace(/[^a-z0-9]/g, "");
            seenKeys.add(key);

            unifiedList.push({
                is_local: true,
                song_id: s.song_id || s.id,
                track_id: s.track_id || "",
                song_name: songName,
                artist_name: artistName,
                album_name: s.album_name || "",
                album_art: s.album_art || "",
                preview_url: s.preview_url || "",
                genre_name: s.genre_name || s.genre || "Music",
                youtube_url: s.youtube_url || ""
            });
        });

        // Add remote songs from global iTunes library
        remoteData.forEach(r => {
            const songName = r.trackName || "";
            const artistName = r.artistName || "";
            const key = (songName + "|" + artistName).toLowerCase().replace(/[^a-z0-9]/g, "");

            if (!seenKeys.has(key)) {
                seenKeys.add(key);
                unifiedList.push({
                    is_local: false,
                    song_id: null,
                    track_id: String(r.trackId || ""),
                    song_name: songName,
                    artist_name: artistName,
                    album_name: r.collectionName || "",
                    album_art: r.artworkUrl100 ? r.artworkUrl100.replace("100x100bb.jpg", "300x300bb.jpg") : "",
                    preview_url: r.previewUrl || "",
                    duration_ms: r.trackTimeMillis || 0,
                    genre_name: r.primaryGenreName || "Music",
                    youtube_url: ""
                });
            }
        });

        if (unifiedList.length === 0) {
            if (countElem) countElem.innerText = "0 tracks";
            resultsDiv.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3>No Songs Found</h3>
                    <p>No results found for "${escapeHtml(query)}" in either local or global music libraries.</p>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="browseAllSongs()">
                        <i class="fa-solid fa-rotate-left"></i> View Seeded Catalog
                    </button>
                </div>
            `;
            return;
        }

        const localCount = localSongs.length;
        const remoteCount = unifiedList.length - localCount;
        if (countElem) {
            countElem.innerText = `${unifiedList.length} tracks (${localCount} in library, ${remoteCount} worldwide)`;
        }

        renderUnifiedSongCards(unifiedList, resultsDiv);

    } catch (err) {
        if (countElem) countElem.innerText = "Error";
        resultsDiv.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i>
                <h3>Search Error</h3>
                <p>Unable to retrieve songs right now. Please verify your connection.</p>
                <button type="button" class="btn btn-secondary btn-sm" onclick="browseAllSongs()">Browse Seeded Library</button>
            </div>
        `;
    }
}

// 2. Render Song Cards with Accessible Video Icon & Playlist Integration
function renderUnifiedSongCards(songs, container) {
    container.innerHTML = "";

    songs.forEach((song, index) => {
        const card = document.createElement("div");
        card.className = "song-card";

        const title = song.song_name || "Untitled";
        const artist = song.artist_name || "Unknown Artist";
        const album = song.album_name || "";
        const genre = song.genre_name || "General";
        const isLocal = song.is_local === true;

        const ytInfo = getYouTubeLinkInfo(title, artist, song.youtube_url);
        const ytVideoId = extractYouTubeVideoId(song.youtube_url);
        const ytThumb = ytVideoId ? `https://img.youtube.com/vi/${ytVideoId}/hqdefault.jpg` : "";
        const primaryArt = song.album_art || ytThumb || "assets/images/placeholder_song.svg";
        const secondaryArt = (primaryArt !== ytThumb && ytThumb) ? ytThumb : "assets/images/placeholder_song.svg";

        card.innerHTML = `
            <div class="song-card-media">
                <img src="${escapeHtml(primaryArt)}" 
                     alt="${escapeHtml(title)} by ${escapeHtml(artist)}" 
                     loading="lazy"
                     onerror="handleSongArtError(this, '${escapeHtml(secondaryArt)}');">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                <h3 style="margin: 0; line-height: 1.3;">${escapeHtml(title)}</h3>
                ${isLocal ? `
                    <span class="status-pill status-active" style="font-size: 0.72rem; padding: 2px 8px; white-space: nowrap;">
                        <i class="fa-solid fa-check"></i> Library
                    </span>
                ` : `
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 8px; background: rgba(33, 55, 34, 0.75); color: var(--forest-100); border: 1px solid var(--border-forest); white-space: nowrap;">
                        <i class="fa-solid fa-globe"></i> Global
                    </span>
                `}
            </div>

            <p style="font-weight: 600; color: #fff; margin: 2px 0 4px 0;">
                <i class="fa-solid fa-user-astronaut" style="color: var(--forest-200); font-size: 0.8rem;"></i> ${escapeHtml(artist)}
            </p>

            ${album ? `<p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0 0 6px 0;"><i class="fa-solid fa-compact-disc"></i> ${escapeHtml(album)}</p>` : ""}

            <span class="genre-tag" style="width: fit-content; margin-bottom: 10px;">${escapeHtml(genre)}</span>

            ${song.preview_url ? `
                <div style="margin: 8px 0 10px 0;">
                    <audio controls style="width: 100%; height: 32px;" src="${song.preview_url}" onplay="recordSongPlay(${song.song_id || 0})"></audio>
                </div>
            ` : `
                <div style="margin: 6px 0 10px 0; font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-volume-xmark"></i> No preview provided by catalog
                </div>
            `}

            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto; padding-top: 14px; border-top: 1px solid var(--border-subtle); align-items: center;">
                <a href="${ytInfo.url}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="btn-youtube-icon" 
                   title="${ytInfo.tooltip}" 
                   aria-label="${ytInfo.tooltip}">
                    <i class="fa-brands fa-youtube"></i> ${ytInfo.isDirect ? 'Watch' : 'Find on YouTube'}
                </a>

                ${ytInfo.isDirect ? `
                    <button type="button" class="btn btn-secondary btn-sm play-modal-btn" title="Open embedded player">
                        <i class="fa-solid fa-play"></i> Player
                    </button>
                ` : ""}

                <button type="button" class="btn btn-primary btn-sm add-playlist-btn" title="Add to your personal playlist">
                    <i class="fa-solid fa-plus"></i> Playlist
                </button>
            </div>
        `;

        // Direct player modal button
        const playModalBtn = card.querySelector(".play-modal-btn");
        if (playModalBtn) {
            playModalBtn.addEventListener("click", () => {
                playTrackVideo(title, ytInfo.url, song.song_id || 0);
            });
        }

        // Add to Playlist button
        const addPlBtn = card.querySelector(".add-playlist-btn");
        if (addPlBtn) {
            addPlBtn.addEventListener("click", () => {
                openPlaylistModalWithSong(song);
            });
        }

        container.appendChild(card);
    });
}

// 3. Open Playlist Modal (Supports both Local Songs and Remote Global Songs)
async function openPlaylistModalWithSong(song) {
    selectedSongForPlaylist = song;
    const title = song.song_name || song.title || "Selected Track";
    const artist = song.artist_name || song.artist || "Unknown Artist";

    document.getElementById("playlistModalSongTitle").innerText = `Track: "${title}" — ${artist}`;
    const select = document.getElementById("userPlaylistSelect");
    select.innerHTML = '<option value="">Loading your playlists...</option>';
    document.getElementById("playlistModal").style.display = "flex";

    try {
        const response = await fetch("api/fetch_user_playlists.php");
        const data = await response.json();
        if (data.playlists && data.playlists.length > 0) {
            select.innerHTML = '<option value="">-- Choose Playlist --</option>';
            data.playlists.forEach(pl => {
                const opt = document.createElement("option");
                opt.value = pl.id;
                opt.textContent = `${pl.name} (${pl.song_count || 0} tracks)`;
                select.appendChild(opt);
            });
        } else {
            select.innerHTML = '<option value="">No playlists found. Create one first!</option>';
        }
    } catch (e) {
        select.innerHTML = '<option value="">Could not load playlists.</option>';
    }
}

function closePlaylistModal() {
    selectedSongForPlaylist = null;
    document.getElementById("playlistModal").style.display = "none";
}

// 4. Confirm Adding Song to Playlist (Saves remote track to local catalog first if needed)
async function confirmAddToPlaylist() {
    const playlistId = document.getElementById("userPlaylistSelect").value;
    if (!playlistId || !selectedSongForPlaylist) {
        alert("Please select a valid playlist.");
        return;
    }

    const btn = document.getElementById("confirmAddToPlaylistBtn");
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving & Adding...';

    try {
        let canonicalSongId = selectedSongForPlaylist.song_id;

        // If this is a remote global song, save or match it in the local catalog first
        if (!canonicalSongId || selectedSongForPlaylist.is_local === false) {
            const formData = new FormData();
            formData.append("track_id", selectedSongForPlaylist.track_id || "");
            formData.append("song_name", selectedSongForPlaylist.song_name || "");
            formData.append("artist_name", selectedSongForPlaylist.artist_name || "");
            formData.append("album_name", selectedSongForPlaylist.album_name || "");
            formData.append("album_art", selectedSongForPlaylist.album_art || "");
            formData.append("preview_url", selectedSongForPlaylist.preview_url || "");
            formData.append("duration_ms", selectedSongForPlaylist.duration_ms || 0);
            formData.append("genre_name", selectedSongForPlaylist.genre_name || "Unknown");

            const addSongRes = await fetch("api/add_song.php", {
                method: "POST",
                body: formData
            });
            const addSongData = await addSongRes.json();

            if (!addSongData.song_id) {
                alert(addSongData.error || "Could not save track into catalog.");
                btn.disabled = false;
                btn.innerHTML = "Add to Playlist";
                return;
            }

            canonicalSongId = addSongData.song_id;
            selectedSongForPlaylist.song_id = canonicalSongId;
            selectedSongForPlaylist.is_local = true;
        }

        // Add canonical song ID to playlist
        const plRes = await fetch("api/add_to_playlist.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `playlist_id=${encodeURIComponent(playlistId)}&song_id=${encodeURIComponent(canonicalSongId)}`
        });
        const plData = await plRes.json();

        if (plData.status === "success") {
            alert(`"${selectedSongForPlaylist.song_name}" was successfully added to your playlist!`);
            closePlaylistModal();
        } else {
            alert(plData.message || "Unable to add track to playlist.");
        }

    } catch (e) {
        alert("Network or database error while adding song to playlist.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = "Add to Playlist";
    }
}

// 5. Browse All Seeded Songs
async function browseAllSongs() {
    const resultsDiv = document.getElementById("results");
    const countElem = document.getElementById("resultsCount");
    const searchInput = document.getElementById("searchInput");
    if (searchInput) searchInput.value = "";

    resultsDiv.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-secondary);"><i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: var(--forest-200);"></i><p>Loading library catalog...</p></div>';

    try {
        const response = await fetch("api/fetch_songs.php");
        const songs = await response.json();

        const formatted = songs.map(s => ({
            is_local: true,
            song_id: s.song_id || s.id,
            track_id: s.track_id || "",
            song_name: s.song_name || s.title,
            artist_name: s.artist_name || s.artist,
            album_name: s.album_name || "",
            album_art: s.album_art || "",
            preview_url: s.preview_url || "",
            genre_name: s.genre_name || s.genre || "General",
            youtube_url: s.youtube_url || ""
        }));

        if (countElem) countElem.innerText = `Showing all ${formatted.length} catalog songs`;
        renderUnifiedSongCards(formatted, resultsDiv);
    } catch (e) {
        resultsDiv.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><h3>Error</h3><p>Could not load library catalog.</p></div>';
    }
}

// 6. Filter by Genre
async function filterSongs() {
    const genreName = document.getElementById("genreFilter").value;
    if (!genreName) {
        browseAllSongs();
        return;
    }

    const resultsDiv = document.getElementById("results");
    const countElem = document.getElementById("resultsCount");
    resultsDiv.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-secondary);"><i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: var(--forest-200);"></i><p>Filtering by genre...</p></div>';

    try {
        const genreRes = await fetch("api/fetch_genres.php");
        const genres = await genreRes.json();
        const found = genres.find(g => g.genre_name.toLowerCase() === genreName.toLowerCase());

        if (!found) {
            resultsDiv.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><h3>No Songs</h3><p>Genre not found.</p></div>';
            return;
        }

        const response = await fetch("api/filter_genre.php?genre_id=" + found.genre_id);
        const songs = await response.json();

        const formatted = songs.map(s => ({
            is_local: true,
            song_id: s.song_id || s.id,
            track_id: s.track_id || "",
            song_name: s.song_name || s.title,
            artist_name: s.artist_name || s.artist,
            album_name: s.album_name || "",
            album_art: s.album_art || "",
            preview_url: s.preview_url || "",
            genre_name: s.genre_name || s.genre || genreName,
            youtube_url: s.youtube_url || ""
        }));

        if (countElem) countElem.innerText = `Showing ${formatted.length} tracks for "${genreName}"`;
        renderUnifiedSongCards(formatted, resultsDiv);
    } catch (e) {
        resultsDiv.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><h3>Error</h3><p>Could not filter songs.</p></div>';
    }
}

// 7. Filter by Artist
async function filterArtists() {
    const artistId = document.getElementById("artistFilter").value;
    if (!artistId) {
        browseAllSongs();
        return;
    }

    const resultsDiv = document.getElementById("results");
    const countElem = document.getElementById("resultsCount");
    resultsDiv.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-secondary);"><i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: var(--accent-gold);"></i><p>Filtering by artist...</p></div>';

    try {
        const response = await fetch("api/filter_artist.php?artist_id=" + artistId);
        const songs = await response.json();

        const formatted = songs.map(s => ({
            is_local: true,
            song_id: s.song_id || s.id,
            track_id: s.track_id || "",
            song_name: s.song_name || s.title,
            artist_name: s.artist_name || s.artist,
            album_name: s.album_name || "",
            album_art: s.album_art || "",
            preview_url: s.preview_url || "",
            genre_name: s.genre_name || s.genre || "General",
            youtube_url: s.youtube_url || ""
        }));

        if (countElem) countElem.innerText = `Showing ${formatted.length} tracks`;
        renderUnifiedSongCards(formatted, resultsDiv);
    } catch (e) {
        resultsDiv.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><h3>Error</h3><p>Could not filter songs by artist.</p></div>';
    }
}

// 8. Load Artists into Dropdown
async function loadArtists() {
    try {
        const response = await fetch("api/fetch_artists.php");
        const artists = await response.json();
        const dropdown = document.getElementById("artistFilter");
        if (!dropdown) return;

        dropdown.innerHTML = '<option value="">All Artists</option>';
        artists.forEach(artist => {
            const option = document.createElement("option");
            option.value = artist.artist_id;
            option.textContent = `${artist.artist_name} (${artist.total_songs})`;
            dropdown.appendChild(option);
        });
    } catch (e) {}
}

// 9. Record Play in History
function recordSongPlay(songId) {
    if (!songId || songId <= 0) return;
    try {
        fetch("api/record_listening.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "song_id=" + encodeURIComponent(songId)
        }).catch(() => {});
    } catch (e) {}
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
    loadArtists();
    browseAllSongs();
});
