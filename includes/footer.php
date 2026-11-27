    </main>
    <footer style="margin-top: 60px; padding: 25px 20px; color: var(--text-muted); font-size: 0.88rem; text-align: center; border-top: 1px solid var(--border-subtle); max-width: 1180px; margin-left: auto; margin-right: auto;">
        <p style="margin: 0 0 6px 0;">&copy; <?= date('Y') ?> <strong>VARP</strong> &bull; Editorial Music Mood Board &bull; Curated in #2C263F &amp; #213722</p>
        <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">Curated sounds, high-contrast imagery, accessible listening, and relational database integration.</p>
    </footer>

    <!-- Video Modal for YouTube playback with genuine pause / resume support -->
    <div id="videoModal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="videoModalTitle">
        <div>
            <div class="modal-header">
                <h3 id="videoModalTitle" style="margin: 0; font-size: 1.2rem; color: #ffffff;">
                    <i class="fa-solid fa-play" style="color: var(--forest-200); margin-right: 8px;"></i>
                    <span id="videoModalSongName">Now Playing</span>
                </h3>
                <button type="button" class="btn btn-danger btn-sm" onclick="closeVideoModal()" aria-label="Close and Pause video">
                    <i class="fa-solid fa-pause"></i> Close &amp; Pause
                </button>
            </div>
            <div id="videoIframeContainer" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--radius-md); background: #000;">
                <!-- YouTube iframe inserted dynamically -->
            </div>
        </div>
    </div>

    <script>
    function playTrackVideo(title, youtubeUrl, songId) {
        if (!youtubeUrl) {
            alert('No video available for this track.');
            return;
        }
        var videoId = '';
        var match = youtubeUrl.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
        if (match && match[1]) {
            videoId = match[1];
        }
        if (!videoId) {
            window.open(youtubeUrl, '_blank');
            return;
        }
        
        // Log listening history when played if songId is provided
        if (songId) {
            try {
                fetch('api/record_listening.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'song_id=' + encodeURIComponent(songId)
                }).catch(function(){});
            } catch(e) {}
        }

        var titleElem = document.getElementById('videoModalSongName');
        if (titleElem) {
            titleElem.innerText = title;
        }
        
        var container = document.getElementById('videoIframeContainer');
        container.innerHTML = '<iframe id="ytActiveIframe" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1&enablejsapi=1" style="position: absolute; top:0; left:0; width:100%; height:100%; border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        document.getElementById('videoModal').style.display = 'flex';
    }

    function closeVideoModal() {
        var container = document.getElementById('videoIframeContainer');
        if (container) {
            // Clearing the container unloads the iframe and genuinely stops/pauses YouTube audio
            container.innerHTML = '';
        }
        var modal = document.getElementById('videoModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Close modal on Escape key press
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeVideoModal();
        }
    });
    </script>
</body>
</html>
