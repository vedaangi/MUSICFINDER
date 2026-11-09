            </div>
        </main>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Music Player Modal / Embed Drawer -->
    <div id="player-modal" class="modal-overlay">
        <div class="modal-card modal-player">
            <div class="modal-header">
                <h3 id="player-title">Now Playing</h3>
                <button class="modal-close" onclick="closePlayerModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="player-artist" class="player-subtext"></p>
                <div class="video-container" id="player-embed-container">
                    <!-- YouTube iframe injected here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add to Playlist Picker Modal -->
    <div id="add-to-playlist-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Add Song to Playlist</h3>
                <button class="modal-close" onclick="closeAddToPlaylistModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-song-id" value="">
                <p class="modal-hint">Select one of your playlists:</p>
                <div id="modal-playlist-list" class="modal-playlist-list">
                    <p class="text-muted">Loading your playlists...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
