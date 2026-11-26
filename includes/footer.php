            </div><!-- .page-body -->
        </main>
    </div><!-- .app-layout -->

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Hidden audio element for audio fallback -->
    <audio id="html5-audio-element" preload="none" style="display: none;"></audio>

    <!-- Docked Bottom Music Player Bar (Member 4 Integration) -->
    <div id="bottom-player-bar" class="bottom-player-bar" style="display: none;" role="region" aria-label="Music Player">
        <div class="player-bar-track-info">
            <div class="player-bar-art">
                <i class="fa-solid fa-compact-disc player-spin-icon" id="player-bar-disc"></i>
            </div>
            <div class="player-bar-text">
                <span class="player-bar-title" id="bar-song-title">Song Title</span>
                <span class="player-bar-artist" id="bar-song-artist">Artist Name</span>
            </div>
            <button class="player-bar-fav-btn" id="bar-fav-btn" onclick="toggleCurrentSongFavorite()" title="Like this song" aria-label="Like this song">
                <i class="fa-regular fa-heart" id="bar-fav-icon"></i>
            </button>
        </div>

        <div class="player-bar-center">
            <div class="player-bar-controls">
                <button class="player-btn" id="bar-shuffle-btn" onclick="toggleShuffle()" title="Shuffle playback" aria-label="Shuffle playback">
                    <i class="fa-solid fa-shuffle"></i>
                </button>
                <button class="player-btn" onclick="skipSong(-1)" title="Previous track" aria-label="Previous track">
                    <i class="fa-solid fa-backward-step"></i>
                </button>
                <button class="player-btn player-btn-main" id="bar-play-toggle-btn" onclick="togglePlayPause()" title="Pause playback" aria-label="Pause playback">
                    <i class="fa-solid fa-pause" id="bar-play-icon"></i>
                </button>
                <button class="player-btn" onclick="skipSong(1)" title="Next track" aria-label="Next track">
                    <i class="fa-solid fa-forward-step"></i>
                </button>
                <button class="player-btn" id="bar-repeat-btn" onclick="toggleRepeat()" title="Repeat playback" aria-label="Repeat playback">
                    <i class="fa-solid fa-repeat"></i>
                </button>
            </div>
            <div class="player-bar-progress-row">
                <span id="player-time-current" class="player-time">0:00</span>
                <div class="player-progress-track" id="player-progress-bar" onclick="seekProgressBar(event)" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Playback timeline">
                    <div class="player-progress-fill" id="player-progress-fill"></div>
                </div>
                <span id="player-time-total" class="player-time">3:30</span>
            </div>
        </div>

        <div class="player-bar-actions">
            <button class="btn btn-secondary btn-sm" id="bar-add-playlist-btn" onclick="openCurrentSongAddToPlaylist()" title="Add to playlist">
                <i class="fa-solid fa-plus"></i> <span class="hide-mobile">Playlist</span>
            </button>
            <button class="btn btn-secondary btn-sm" onclick="expandToVideoModal()" title="Watch video on YouTube">
                <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> <span class="hide-mobile">Video</span>
            </button>
            <button class="player-btn-icon" onclick="toggleMute()" title="Mute or unmute volume" id="mute-btn" aria-label="Mute or unmute">
                <i class="fa-solid fa-volume-high" id="volume-icon"></i>
            </button>
            <button class="player-btn-icon" onclick="closePlayer()" title="Close player" aria-label="Close player" style="color: var(--text-muted);">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Music Player Video Modal (YouTube Embed) -->
    <div id="player-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="player-title">
        <div class="modal-card modal-player">
            <div class="modal-header">
                <div>
                    <h3 id="player-title">Now Playing</h3>
                    <p id="player-artist" class="player-subtext" style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 2px;"></p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a id="player-direct-yt-link" href="#" target="_blank" class="btn btn-secondary btn-sm" style="display: none;" title="Watch directly on YouTube">
                        <i class="fa-brands fa-youtube" style="color: #ef4444;"></i> Open YouTube
                    </a>
                    <button class="modal-close" onclick="closePlayerModal()" aria-label="Close modal">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <div id="player-error-banner" style="display: none; padding: 12px 14px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--radius); color: #be185d; font-size: 0.88rem; margin-bottom: 12px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <span id="player-error-text">Playback issue detected.</span>
                </div>
                <div class="video-container" id="player-embed-container">
                    <!-- YouTube iframe player container -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add to Playlist Picker Modal -->
    <div id="add-to-playlist-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="add-pl-title">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="add-pl-title">Add Song to Playlist</h3>
                <button class="modal-close" onclick="closeAddToPlaylistModal()" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-song-id" value="">
                <p class="modal-hint">Select a playlist to add this song:</p>
                <div id="modal-playlist-list" class="modal-playlist-list">
                    <p class="text-muted">Loading your playlists...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Load YouTube IFrame Player API -->
    <script src="https://www.youtube.com/iframe_api"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
