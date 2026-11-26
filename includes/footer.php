    </div>
    <footer style="margin-top: 50px; padding: 20px; color: #888; font-size: 13px; text-align: center;">
        <p>&copy; <?= date('Y') ?> MusicFinder - Database & Web Project</p>
    </footer>

    <!-- Video Modal for YouTube playback -->
    <div id="videoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 10px; padding: 20px; width: 90%; max-width: 640px; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 id="videoModalTitle" style="margin: 0; color: #333;">Now Playing</h3>
                <button onclick="closeVideoModal()" style="background: #ef4444; padding: 6px 12px; font-size: 14px; border-radius: 4px;">Close & Pause</button>
            </div>
            <div id="videoIframeContainer" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 6px;">
                <!-- YouTube iframe inserted dynamically -->
            </div>
        </div>
    </div>

    <script>
    function playTrackVideo(title, youtubeUrl) {
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
        document.getElementById('videoModalTitle').innerText = title;
        var container = document.getElementById('videoIframeContainer');
        container.innerHTML = '<iframe id="ytActiveIframe" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1&enablejsapi=1" style="position: absolute; top:0; left:0; width:100%; height:100%;" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        document.getElementById('videoModal').style.display = 'flex';
    }

    function closeVideoModal() {
        var container = document.getElementById('videoIframeContainer');
        container.innerHTML = '';
        document.getElementById('videoModal').style.display = 'none';
    }
    </script>
</body>
</html>
