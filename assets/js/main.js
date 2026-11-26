/**
 * MusicFinder - Main Frontend JavaScript
 * Members 3 & 4: Player Controls, Pause/Resume, Playlists, Favorites & UI
 */

let currentSong = null;
let isPlaying = false;
let isMuted = false;
let shuffleMode = false;
let repeatMode = 0; // 0: Off, 1: Repeat All, 2: Repeat One
let progressSeconds = 0;
let totalDurationSeconds = 210; // Default 3:30 estimation until metadata loads
let progressInterval = null;

// YouTube IFrame Player Instance
let ytPlayer = null;
let ytApiReady = false;

// Global YouTube callback
window.onYouTubeIframeAPIReady = function() {
    ytApiReady = true;
};

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'fa-circle-info';
    if (type === 'success') icon = 'fa-circle-check';
    if (type === 'error') icon = 'fa-circle-exclamation';

    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}

/**
 * Play a track (Starts/switches playback and records history once)
 */
function playSong(songId, title, artist, youtubeUrl) {
    currentSong = {
        id: songId,
        title: title,
        artist: artist,
        youtubeUrl: youtubeUrl
    };

    // 1. Record listening history once via POST API
    fetch('api/record_listening.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `song_id=${encodeURIComponent(songId)}`
    })
    .then(res => res.json())
    .catch(() => {});

    // 2. Update Bottom Player Bar UI
    const playerBar = document.getElementById('bottom-player-bar');
    const barTitle = document.getElementById('bar-song-title');
    const barArtist = document.getElementById('bar-song-artist');

    if (playerBar && barTitle && barArtist) {
        barTitle.textContent = title;
        barArtist.textContent = artist;
        playerBar.style.display = 'flex';
        document.body.style.paddingBottom = '88px';
    }

    // 3. Reset timeline progress
    progressSeconds = 0;
    updateProgressDisplay();

    // 4. Update favorite heart state on player bar
    checkCurrentSongFavorite(songId);

    // 5. Start real video / audio playback
    setupPlayerPlayback(youtubeUrl, title);

    setPlayerState(true);
    showToast(`Now playing "${title}" by ${artist}`, 'info');
}

/**
 * Configure and trigger YouTube / audio playback
 */
function setupPlayerPlayback(youtubeUrl, title) {
    const titleEl = document.getElementById('player-title');
    const artistEl = document.getElementById('player-artist');
    const errorBanner = document.getElementById('player-error-banner');
    const directLink = document.getElementById('player-direct-yt-link');
    const embedEl = document.getElementById('player-embed-container');

    if (titleEl) titleEl.textContent = title;
    if (artistEl && currentSong) artistEl.textContent = currentSong.artist;
    if (errorBanner) errorBanner.style.display = 'none';

    let videoId = '';
    if (youtubeUrl) {
        if (youtubeUrl.includes('watch?v=')) {
            videoId = youtubeUrl.split('watch?v=')[1].split('&')[0];
        } else if (youtubeUrl.includes('youtu.be/')) {
            videoId = youtubeUrl.split('youtu.be/')[1].split('?')[0];
        }
    }

    if (directLink) {
        if (youtubeUrl) {
            directLink.href = youtubeUrl;
            directLink.style.display = 'inline-flex';
        } else {
            directLink.style.display = 'none';
        }
    }

    if (videoId) {
        // If YouTube IFrame API is ready and player exists, load video into existing player
        if (ytPlayer && typeof ytPlayer.loadVideoById === 'function') {
            try {
                ytPlayer.loadVideoById(videoId);
                ytPlayer.playVideo();
                return;
            } catch (e) {
                // re-initialize below if loadVideoById fails
            }
        }

        // If YT API is ready, create player with event hooks
        if (window.YT && window.YT.Player) {
            embedEl.innerHTML = '<div id="yt-player-target"></div>';
            ytPlayer = new YT.Player('yt-player-target', {
                videoId: videoId,
                playerVars: {
                    autoplay: 1,
                    enablejsapi: 1,
                    playsinline: 1,
                    rel: 0
                },
                events: {
                    onReady: function(event) {
                        try { event.target.playVideo(); } catch (err) {}
                    },
                    onStateChange: onYouTubeStateChange,
                    onError: onYouTubeError
                }
            });
        } else {
            // Direct iframe embed with enablejsapi=1 fallback
            embedEl.innerHTML = `<iframe id="yt-player-iframe" src="https://www.youtube.com/embed/${videoId}?autoplay=1&enablejsapi=1&origin=${encodeURIComponent(window.location.origin)}" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
        }
    } else {
        // Fallback for tracks without YouTube URLs
        embedEl.innerHTML = `<div style="padding: 60px 20px; text-align: center; color: var(--text-secondary);">
            <i class="fa-solid fa-compact-disc player-spin-icon spinning" style="font-size: 3rem; color: var(--accent); margin-bottom: 14px; display: block;"></i>
            <h4 style="color: var(--text-primary); margin-bottom: 6px;">Audio Stream Simulation</h4>
            <p style="font-size: 0.9rem;">Simulating real-time playback for <strong>${escapeHtml(title)}</strong></p>
        </div>`;
    }
}

/**
 * YouTube State Change Event Handler
 */
function onYouTubeStateChange(event) {
    if (event.data === YT.PlayerState.PLAYING) {
        setPlayerState(true);
        // Sync duration if available
        if (ytPlayer && typeof ytPlayer.getDuration === 'function') {
            const dur = Math.floor(ytPlayer.getDuration());
            if (dur > 0) totalDurationSeconds = dur;
        }
    } else if (event.data === YT.PlayerState.PAUSED) {
        setPlayerState(false);
    } else if (event.data === YT.PlayerState.ENDED) {
        handleSongEnded();
    }
}

/**
 * YouTube Error Handling
 */
function onYouTubeError(event) {
    const errorBanner = document.getElementById('player-error-banner');
    const errorText = document.getElementById('player-error-text');
    let msg = "Video playback is unavailable. You can watch it directly on YouTube using the button above.";

    if (event.data === 101 || event.data === 150) {
        msg = "The video owner does not allow embedded playback. Please click 'Open YouTube' to listen.";
    } else if (event.data === 100 || event.data === 2) {
        msg = "Track video was removed or is not found. Continuing audio playback.";
    }

    if (errorBanner && errorText) {
        errorText.textContent = msg;
        errorBanner.style.display = 'block';
    }
    showToast(msg, 'error');
}

/**
 * REAL PAUSE AND RESUME CONTROL
 * Commands the actual playing YouTube player, iframe, or audio element.
 */
function togglePlayPause() {
    if (!currentSong) return;

    const iframe = document.getElementById('yt-player-iframe') || (ytPlayer && typeof ytPlayer.getIframe === 'function' ? ytPlayer.getIframe() : null);
    const audioEl = document.getElementById('html5-audio-element');

    if (isPlaying) {
        // --- PAUSE CURRENT PLAYBACK ---
        // 1. Official YouTube API
        if (ytPlayer && typeof ytPlayer.pauseVideo === 'function') {
            try { ytPlayer.pauseVideo(); } catch (e) {}
        }
        // 2. Fallback postMessage to iframe
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
            } catch (e) {}
        }
        // 3. Audio element fallback
        if (audioEl) {
            try { audioEl.pause(); } catch (e) {}
        }

        setPlayerState(false);
        showToast('Playback paused', 'info');
    } else {
        // --- RESUME CURRENT PLAYBACK ---
        // 1. Official YouTube API
        if (ytPlayer && typeof ytPlayer.playVideo === 'function') {
            try { ytPlayer.playVideo(); } catch (e) {}
        }
        // 2. Fallback postMessage to iframe
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
            } catch (e) {}
        }
        // 3. Audio element fallback
        if (audioEl && audioEl.src) {
            try { audioEl.play(); } catch (e) {}
        }

        setPlayerState(true);
        showToast('Playback resumed', 'info');
    }
}

/**
 * Synchronize UI elements with current playing/paused state
 */
function setPlayerState(playing) {
    isPlaying = playing;
    const playIcon = document.getElementById('bar-play-icon');
    const playBtn = document.getElementById('bar-play-toggle-btn');
    const discIcon = document.getElementById('player-bar-disc');

    if (playing) {
        if (playIcon) playIcon.className = 'fa-solid fa-pause';
        if (playBtn) {
            playBtn.title = 'Pause playback';
            playBtn.setAttribute('aria-label', 'Pause playback');
        }
        if (discIcon) discIcon.classList.add('spinning');
        startProgressTimer();
    } else {
        if (playIcon) playIcon.className = 'fa-solid fa-play';
        if (playBtn) {
            playBtn.title = 'Resume playback';
            playBtn.setAttribute('aria-label', 'Resume playback');
        }
        if (discIcon) discIcon.classList.remove('spinning');
        clearInterval(progressInterval);
    }
}

function startProgressTimer() {
    clearInterval(progressInterval);
    progressInterval = setInterval(() => {
        if (!isPlaying) return;

        // Sync with actual player currentTime if available
        if (ytPlayer && typeof ytPlayer.getCurrentTime === 'function') {
            const cur = Math.floor(ytPlayer.getCurrentTime());
            if (cur > 0) progressSeconds = cur;
            else progressSeconds++;
        } else {
            progressSeconds++;
        }

        if (progressSeconds >= totalDurationSeconds) {
            handleSongEnded();
        } else {
            updateProgressDisplay();
        }
    }, 1000);
}

function updateProgressDisplay() {
    const currentEl = document.getElementById('player-time-current');
    const totalEl = document.getElementById('player-time-total');
    const fillEl = document.getElementById('player-progress-fill');
    const trackBar = document.getElementById('player-progress-bar');

    if (currentEl) currentEl.textContent = formatTime(progressSeconds);
    if (totalEl) totalEl.textContent = formatTime(totalDurationSeconds);
    if (fillEl) {
        const percent = Math.min(100, (progressSeconds / totalDurationSeconds) * 100);
        fillEl.style.width = `${percent}%`;
    }
    if (trackBar) {
        const percent = Math.min(100, (progressSeconds / totalDurationSeconds) * 100);
        trackBar.setAttribute('aria-valuenow', Math.floor(percent));
    }
}

function seekProgressBar(e) {
    const bar = document.getElementById('player-progress-bar');
    if (!bar) return;
    const rect = bar.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const ratio = Math.max(0, Math.min(1, clickX / rect.width));
    progressSeconds = Math.floor(ratio * totalDurationSeconds);
    
    // Command underlying player to seek
    if (ytPlayer && typeof ytPlayer.seekTo === 'function') {
        try { ytPlayer.seekTo(progressSeconds, true); } catch (e) {}
    } else {
        const iframe = document.getElementById('yt-player-iframe');
        if (iframe && iframe.contentWindow) {
            try {
                iframe.contentWindow.postMessage(`{"event":"command","func":"seekTo","args":[${progressSeconds}, true]}`, '*');
            } catch (e) {}
        }
    }
    updateProgressDisplay();
}

function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
}

function toggleMute() {
    isMuted = !isMuted;
    const icon = document.getElementById('volume-icon');
    if (icon) {
        icon.className = isMuted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
    }

    if (ytPlayer && typeof ytPlayer.mute === 'function') {
        try {
            if (isMuted) ytPlayer.mute();
            else ytPlayer.unMute();
        } catch (e) {}
    } else {
        const iframe = document.getElementById('yt-player-iframe');
        if (iframe && iframe.contentWindow) {
            try {
                const cmd = isMuted ? 'mute' : 'unMute';
                iframe.contentWindow.postMessage(`{"event":"command","func":"${cmd}","args":""}`, '*');
            } catch (e) {}
        }
    }
    showToast(isMuted ? 'Muted' : 'Unmuted', 'info');
}

/**
 * Handle Track Completion with Repeat & Shuffle logic
 */
function handleSongEnded() {
    if (repeatMode === 2) {
        // Repeat One
        progressSeconds = 0;
        if (ytPlayer && typeof ytPlayer.seekTo === 'function') {
            ytPlayer.seekTo(0, true);
            ytPlayer.playVideo();
        } else if (currentSong) {
            playSong(currentSong.id, currentSong.title, currentSong.artist, currentSong.youtubeUrl);
        }
    } else if (shuffleMode) {
        // Shuffle play a random track
        skipSong(0, true);
    } else {
        // Normal advance to next song
        skipSong(1);
    }
}

/**
 * Skip Tracks (with Shuffle & Repeat support)
 */
function skipSong(direction, forceRandom = false) {
    const playIcons = Array.from(document.querySelectorAll('.song-play-icon'));
    if (playIcons.length === 0) return;

    if (shuffleMode || forceRandom) {
        // Pick a random track different from current
        let randIndex = Math.floor(Math.random() * playIcons.length);
        playIcons[randIndex].click();
        return;
    }

    let currentIndex = -1;
    if (currentSong) {
        currentIndex = playIcons.findIndex(el => {
            const row = el.closest('.song-card') || el.closest('.card');
            return row && row.textContent.includes(currentSong.title);
        });
    }

    let nextIndex = currentIndex + direction;
    if (nextIndex < 0) {
        if (repeatMode === 1) nextIndex = playIcons.length - 1;
        else nextIndex = 0;
    }
    if (nextIndex >= playIcons.length) {
        if (repeatMode === 1) nextIndex = 0;
        else return; // Stop at end if repeat is off
    }

    if (playIcons[nextIndex]) {
        playIcons[nextIndex].click();
    }
}

function toggleShuffle() {
    shuffleMode = !shuffleMode;
    const btn = document.getElementById('bar-shuffle-btn');
    if (btn) {
        if (shuffleMode) {
            btn.classList.add('active');
            showToast('Shuffle enabled', 'info');
        } else {
            btn.classList.remove('active');
            showToast('Shuffle disabled', 'info');
        }
    }
}

function toggleRepeat() {
    repeatMode = (repeatMode + 1) % 3; // 0: off, 1: all, 2: one
    const btn = document.getElementById('bar-repeat-btn');
    if (!btn) return;

    if (repeatMode === 1) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="fa-solid fa-repeat"></i>';
        showToast('Repeat all enabled', 'info');
    } else if (repeatMode === 2) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="fa-solid fa-repeat" style="position:relative;"><span style="position:absolute;top:-4px;right:-6px;font-size:0.6rem;font-weight:900;">1</span></i>';
        showToast('Repeat one track enabled', 'info');
    } else {
        btn.classList.remove('active');
        btn.innerHTML = '<i class="fa-solid fa-repeat"></i>';
        showToast('Repeat disabled', 'info');
    }
}

function expandToVideoModal() {
    if (!currentSong) return;
    const modal = document.getElementById('player-modal');
    if (modal) modal.style.display = 'flex';
}

function closePlayerModal() {
    const modal = document.getElementById('player-modal');
    if (modal) modal.style.display = 'none';
}

function closePlayer() {
    if (isPlaying) togglePlayPause();
    const playerBar = document.getElementById('bottom-player-bar');
    if (playerBar) playerBar.style.display = 'none';
    document.body.style.paddingBottom = '0';
    closePlayerModal();
}

/**
 * FAVORITES (LIKED SONGS) FEATURE
 */
function toggleFavorite(songId, btnEl) {
    const formData = new URLSearchParams();
    formData.append('song_id', songId);

    fetch('api/toggle_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => {
        if (res.status === 401) {
            showToast('Please sign in to like songs', 'error');
            setTimeout(() => { window.location.href = 'login.php'; }, 1000);
            return null;
        }
        return res.json();
    })
    .then(data => {
        if (!data || !data.success) return;
        const isFav = data.is_favorite;
        showToast(data.message, isFav ? 'success' : 'info');

        // Update all buttons for this songId on page
        document.querySelectorAll(`.fav-btn-${songId}`).forEach(btn => {
            const icon = btn.querySelector('i');
            if (isFav) {
                btn.classList.add('active');
                if (icon) {
                    icon.className = 'fa-solid fa-heart';
                    icon.style.color = 'var(--accent)';
                }
            } else {
                btn.classList.remove('active');
                if (icon) {
                    icon.className = 'fa-regular fa-heart';
                    icon.style.color = '';
                }
            }
        });

        // Update Player Bar favorite button if current track
        if (currentSong && currentSong.id == songId) {
            updatePlayerBarFavoriteIcon(isFav);
        }

        // If currently on favorites.php, remove row smoothly
        const row = document.getElementById(`fav-row-${songId}`);
        if (row && !isFav) {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';
            row.style.transition = 'all 0.3s ease';
            setTimeout(() => row.remove(), 300);
        }
    })
    .catch(() => showToast('Error updating favorite', 'error'));
}

function toggleCurrentSongFavorite() {
    if (!currentSong) return;
    const btn = document.getElementById('bar-fav-btn');
    toggleFavorite(currentSong.id, btn);
}

function checkCurrentSongFavorite(songId) {
    // Check if the page has an active favorite button for this song
    const pageBtn = document.querySelector(`.fav-btn-${songId}`);
    if (pageBtn) {
        updatePlayerBarFavoriteIcon(pageBtn.classList.contains('active'));
    }
}

function updatePlayerBarFavoriteIcon(isFav) {
    const icon = document.getElementById('bar-fav-icon');
    const btn = document.getElementById('bar-fav-btn');
    if (!icon || !btn) return;

    if (isFav) {
        btn.classList.add('active');
        icon.className = 'fa-solid fa-heart';
        icon.style.color = 'var(--accent)';
    } else {
        btn.classList.remove('active');
        icon.className = 'fa-regular fa-heart';
        icon.style.color = '';
    }
}

function openCurrentSongAddToPlaylist() {
    if (currentSong) {
        openAddToPlaylistModal(currentSong.id, currentSong.title);
    }
}

/**
 * Add to Playlist Modal & Flow
 */
function openAddToPlaylistModal(songId, songTitle) {
    const modal = document.getElementById('add-to-playlist-modal');
    const songIdInput = document.getElementById('modal-song-id');
    const listContainer = document.getElementById('modal-playlist-list');

    if (!modal) return;

    songIdInput.value = songId;
    listContainer.innerHTML = '<p class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Loading your playlists...</p>';
    modal.style.display = 'flex';

    fetch('api/fetch_playlists.php')
        .then(res => {
            if (res.status === 401) {
                listContainer.innerHTML = `
                    <p style="color: var(--danger); font-size: 0.9rem;">You must be signed in to add songs to a playlist.</p>
                    <a href="login.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Sign In Now</a>
                `;
                throw new Error("unauthorized");
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success' && data.playlists.length > 0) {
                listContainer.innerHTML = '';
                data.playlists.forEach(pl => {
                    const btn = document.createElement('button');
                    btn.className = 'playlist-pick-btn';
                    const plName = pl.name || pl.playlist_name || 'Untitled';
                    btn.innerHTML = `
                        <span><strong>${escapeHtml(plName)}</strong></span>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">${pl.song_count} songs</span>
                    `;
                    btn.onclick = () => submitAddSong(pl.id, songId);
                    listContainer.appendChild(btn);
                });
            } else {
                listContainer.innerHTML = `
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">You haven't created any playlists yet.</p>
                    <a href="playlists.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Create a Playlist</a>
                `;
            }
        })
        .catch(err => {
            if (err.message !== 'unauthorized') {
                listContainer.innerHTML = '<p style="color: var(--danger);">Failed to load playlists. Please try again.</p>';
            }
        });
}

function closeAddToPlaylistModal() {
    const modal = document.getElementById('add-to-playlist-modal');
    if (modal) modal.style.display = 'none';
}

function submitAddSong(playlistId, songId) {
    const formData = new URLSearchParams();
    formData.append('playlist_id', playlistId);
    formData.append('song_id', songId);

    fetch('api/add_to_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        closeAddToPlaylistModal();
        if (data.status === 'success') {
            showToast('Song added to playlist!', 'success');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => {
        showToast('Error adding song to playlist', 'error');
    });
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Global click outside to dismiss modals
window.onclick = function(event) {
    const playerModal = document.getElementById('player-modal');
    const addModal = document.getElementById('add-to-playlist-modal');
    const createModal = document.getElementById('create-modal');
    const renameModal = document.getElementById('rename-modal');
    const addSongModal = document.getElementById('add-song-modal');

    if (event.target === playerModal) closePlayerModal();
    if (event.target === addModal) closeAddToPlaylistModal();
    if (event.target === createModal && typeof closeCreateModal === 'function') closeCreateModal();
    if (event.target === renameModal && typeof closeRenameModal === 'function') closeRenameModal();
    if (event.target === addSongModal && typeof closeAddSongModal === 'function') closeAddSongModal();
};

// Global escape key to close modals
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePlayerModal();
        closeAddToPlaylistModal();
        if (typeof closeCreateModal === 'function') closeCreateModal();
        if (typeof closeRenameModal === 'function') closeRenameModal();
        if (typeof closeAddSongModal === 'function') closeAddSongModal();
    }
});
