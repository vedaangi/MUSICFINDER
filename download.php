<?php
require_once __DIR__ . '/check_subscription.php';
$subscription = requirePremiumSubscription($conn);

$user_id = (int)$_SESSION['user_id'];
$allowed_mime_types = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',
    'flac' => 'audio/flac'
];

$permitted_dir = realpath(__DIR__ . '/audio');

function resolvePermittedAudioPath(?string $permitted_dir, ?string $stored_filename, array $allowed_mime_types): ?array
{
    if ($permitted_dir === false || $permitted_dir === null || !is_string($stored_filename)) {
        return null;
    }

    $stored_filename = trim($stored_filename);
    if ($stored_filename === '' || str_contains($stored_filename, "\0")) {
        return null;
    }

    $basename = basename($stored_filename);
    if ($basename !== $stored_filename || str_contains($basename, '..') || str_contains($basename, '/') || str_contains($basename, '\\')) {
        return null;
    }

    $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
    if (!isset($allowed_mime_types[$extension])) {
        return null;
    }

    $candidate = $permitted_dir . DIRECTORY_SEPARATOR . $basename;
    $resolved = realpath($candidate);
    if ($resolved === false) {
        return null;
    }

    $prefix = rtrim($permitted_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($resolved, $prefix) || !is_file($resolved) || !is_readable($resolved)) {
        return null;
    }

    return [
        'path' => $resolved,
        'extension' => $extension,
        'mime' => $allowed_mime_types[$extension]
    ];
}

$error_message = '';

if (isset($_GET['song_id'])) {
    $song_id = filter_input(INPUT_GET, 'song_id', FILTER_VALIDATE_INT);

    if ($song_id === false || $song_id === null || $song_id <= 0) {
        http_response_code(400);
        $error_message = 'Invalid song selection.';
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT song_id, song_name, audio_file FROM songs WHERE song_id = ? LIMIT 1"
            );
            $stmt->bind_param('i', $song_id);
            $stmt->execute();
            $song = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$song) {
                http_response_code(404);
                $error_message = 'The requested song could not be found.';
            } else {
                $file_info = resolvePermittedAudioPath($permitted_dir, $song['audio_file'] ?? null, $allowed_mime_types);

                if ($file_info === null) {
                    http_response_code(404);
                    $error_message = 'No permitted audio file is available for download for this song.';
                } else {
                    $logStmt = $conn->prepare("INSERT INTO downloads (user_id, song_id) VALUES (?, ?)");
                    if ($logStmt) {
                        $logStmt->bind_param('ii', $user_id, $song_id);
                        $logStmt->execute();
                        $logStmt->close();
                    }

                    $safe_name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $song['song_name']);
                    $safe_name = trim((string)$safe_name, ' ._');
                    if ($safe_name === '') {
                        $safe_name = 'track_' . $song_id;
                    }
                    $download_filename = $safe_name . '.' . $file_info['extension'];

                    header('Content-Description: File Transfer');
                    header('Content-Type: ' . $file_info['mime']);
                    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
                    header('Content-Length: ' . filesize($file_info['path']));
                    header('X-Content-Type-Options: nosniff');
                    header('Cache-Control: private, no-store, max-age=0');

                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }

                    readfile($file_info['path']);
                    exit;
                }
            }
        } catch (Throwable $e) {
            http_response_code(500);
            $error_message = 'Unable to process this download right now.';
        }
    }
}

$eligible_songs = [];
try {
    $res = $conn->query(
        "SELECT s.song_id, s.song_name, s.audio_file, a.artist_name, g.genre_name
         FROM songs s
         LEFT JOIN artists a ON s.artist_id = a.artist_id
         LEFT JOIN genres g ON s.genre_id = g.genre_id
         WHERE s.audio_file IS NOT NULL AND s.audio_file <> ''
         ORDER BY s.song_name ASC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (resolvePermittedAudioPath($permitted_dir, $row['audio_file'], $allowed_mime_types) !== null) {
                $eligible_songs[] = $row;
            }
        }
    }
} catch (Throwable $ignored) {
}

$recent_downloads = [];
try {
    $histStmt = $conn->prepare(
        "SELECT d.downloaded_at, s.song_name, a.artist_name
         FROM downloads d
         INNER JOIN songs s ON d.song_id = s.song_id
         LEFT JOIN artists a ON s.artist_id = a.artist_id
         WHERE d.user_id = ?
         ORDER BY d.downloaded_at DESC
         LIMIT 10"
    );
    if ($histStmt) {
        $histStmt->bind_param('i', $user_id);
        $histStmt->execute();
        $histRes = $histStmt->get_result();
        while ($row = $histRes->fetch_assoc()) {
            $recent_downloads[] = $row;
        }
        $histStmt->close();
    }
} catch (Throwable $ignored) {
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Eligible Music Downloads</h1>
        <p class="page-subtitle">Download permitted royalty-free or authorized tracks included with Music Finder Premium.</p>
    </div>
    <a href="premium_dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Premium Hub
    </a>
</div>

<?php if ($error_message !== ''): ?>
    <div class="alert-box alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error_message) ?></span>
    </div>
<?php endif; ?>

<?php if (empty($eligible_songs)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-folder-open"></i>
        <h3>No Eligible Audio Files Available</h3>
        <p>No permitted audio files are currently configured for download. Place licensed or royalty-free audio files in the permitted audio directory and assign their filename to <code>songs.audio_file</code>.</p>
        <a href="premium_dashboard.php" class="btn btn-secondary">Back to Premium Hub</a>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 32px;">
        <?php foreach ($eligible_songs as $song): ?>
            <div class="song-card">
                <div class="song-info">
                    <div class="song-play-icon" style="cursor: default;">
                        <i class="fa-solid fa-music"></i>
                    </div>
                    <div class="song-details">
                        <span class="song-title"><?= htmlspecialchars($song['song_name']) ?></span>
                        <span class="song-artist"><?= htmlspecialchars($song['artist_name'] ?? 'Unknown Artist') ?></span>
                        <span class="genre-tag"><?= htmlspecialchars($song['genre_name'] ?? 'Unknown') ?></span>
                    </div>
                </div>
                <div class="song-actions">
                    <a href="download.php?song_id=<?= (int)$song['song_id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($recent_downloads)): ?>
    <div class="page-header">
        <h2 style="font-size: 1.25rem; color: #fff;">Your Recent Downloads</h2>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Song</th>
                    <th>Artist</th>
                    <th>Downloaded At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_downloads as $dl): ?>
                    <tr>
                        <td><?= htmlspecialchars($dl['song_name']) ?></td>
                        <td><?= htmlspecialchars($dl['artist_name'] ?? 'Unknown Artist') ?></td>
                        <td><?= htmlspecialchars($dl['downloaded_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
