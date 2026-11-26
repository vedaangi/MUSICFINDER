function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function playSong(songId, title, artist, youtubeUrl) {
    fetch('api/record_listening.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `song_id=${encodeURIComponent(songId)}`
    })
    .then(res => res.json())
    .catch(() => {});

    const modal = document.getElementById('player-modal');
    const titleEl = document.getElementById('player-title');
    const artistEl = document.getElementById('player-artist');
    const embedEl = document.getElementById('player-embed-container');

    if (!modal) return;

    titleEl.textContent = title;
    artistEl.textContent = artist;

    let videoId = '';
    if (youtubeUrl) {
        if (youtubeUrl.includes('watch?v=')) {
            videoId = youtubeUrl.split('watch?v=')[1].split('&')[0];
        } else if (youtubeUrl.includes('youtu.be/')) {
            videoId = youtubeUrl.split('youtu.be/')[1].split('?')[0];
        }
    }

    if (videoId) {
        embedEl.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
    } else {
        embedEl.innerHTML = `<div style="padding: 40px; text-align: center; color: #9ca3af;">Audio stream simulation for "${title}"</div>`;
    }

    modal.style.display = 'flex';
}

function closePlayerModal() {
    const modal = document.getElementById('player-modal');
    const embedEl = document.getElementById('player-embed-container');
    if (modal) {
        modal.style.display = 'none';
        embedEl.innerHTML = '';
    }
}

function openAddToPlaylistModal(songId, songTitle) {
    const modal = document.getElementById('add-to-playlist-modal');
    const songIdInput = document.getElementById('modal-song-id');
    const listContainer = document.getElementById('modal-playlist-list');

    if (!modal) return;

    songIdInput.value = songId;
    listContainer.innerHTML = '<p class="text-muted">Loading your playlists...</p>';
    modal.style.display = 'flex';

    fetch('api/fetch_playlists.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.playlists.length > 0) {
                listContainer.innerHTML = '';
                data.playlists.forEach(pl => {
                    const btn = document.createElement('button');
                    btn.className = 'playlist-pick-btn';
                    btn.innerHTML = `
                        <span><strong>${escapeHtml(pl.name)}</strong></span>
                        <span style="font-size: 0.8rem; color: #9ca3af;">${pl.song_count} songs</span>
                    `;
                    btn.onclick = () => submitAddSong(pl.id, songId);
                    listContainer.appendChild(btn);
                });
            } else {
                listContainer.innerHTML = `
                    <p style="color: #9ca3af; font-size: 0.9rem;">No playlists found.</p>
                    <a href="playlists.php" class="btn btn-primary btn-sm" style="margin-top: 8px;">Create a Playlist</a>
                `;
            }
        })
        .catch(() => {
            listContainer.innerHTML = '<p style="color: #ef4444;">Failed to load playlists.</p>';
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.onclick = function(event) {
    const playerModal = document.getElementById('player-modal');
    const addModal = document.getElementById('add-to-playlist-modal');
    if (event.target === playerModal) closePlayerModal();
    if (event.target === addModal) closeAddToPlaylistModal();
};
