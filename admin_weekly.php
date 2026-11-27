<?php
require_once __DIR__ . '/check_subscription.php';
$admin_user_id = requireAdmin($conn);

function isValidIsoDate(string $value): bool
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $dt !== false && $dt->format('Y-m-d') === $value;
}

// Validate image URL source for legitimacy and security
function sanitizeAndValidateImageUrl(?string $url): ?string
{
    if ($url === null || trim($url) === '') {
        return null;
    }
    $url = trim($url);

    // Allow approved local relative paths
    if (str_starts_with($url, 'assets/images/') || str_starts_with($url, 'uploads/')) {
        return $url;
    }

    // Must be valid URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array(strtolower((string)$scheme), ['http', 'https'], true)) {
        return null;
    }

    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $validExts = ['jpg', 'jpeg', 'png', 'webp', 'svg', ''];
    if (!in_array($ext, $validExts, true)) {
        return null;
    }

    return $url;
}

// Handle secure file upload for posters/images
function handleSecureImageUpload(string $inputName, string $targetDir): ?string
{
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed (Code: {$file['error']}).");
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Uploaded image exceeds 5MB size limit.");
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception("Security Alert: Invalid upload request.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    ];

    if (!array_key_exists($mime, $allowed)) {
        throw new Exception("Invalid file type '$mime'. Only JPG, PNG, WEBP, and SVG images are allowed.");
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $ext = $allowed[$mime];
    $newFilename = 'media_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception("Unable to save uploaded image file to storage.");
    }

    return 'uploads/weekly/' . $newFilename;
}

$message = '';
$message_type = '';

$form = [
    'weekly_id' => 0,
    'genre_id' => 0,
    'movie_name' => '',
    'movie_year' => 2023,
    'movie_description' => '',
    'movie_poster_url' => '',
    'movie_music_fit' => '',
    'character_name' => '',
    'character_show' => '',
    'character_description' => '',
    'character_traits' => '',
    'character_music_match' => '',
    'character_image_url' => '',
    'week_start' => '',
    'week_end' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please refresh and try again.';
        $message_type = 'error';
    } else {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'delete') {
            $delete_id = filter_input(INPUT_POST, 'weekly_id', FILTER_VALIDATE_INT);
            if ($delete_id === false || $delete_id === null || $delete_id <= 0) {
                $message = 'Invalid entry selected for deletion.';
                $message_type = 'error';
            } else {
                try {
                    $delStmt = $conn->prepare("DELETE FROM weekly_movies WHERE weekly_id = ?");
                    $delStmt->bind_param('i', $delete_id);
                    $delStmt->execute();
                    $deleted = $delStmt->affected_rows;
                    $delStmt->close();

                    if ($deleted > 0) {
                        $message = 'Weekly recommendation entry deleted successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Weekly entry was not found.';
                        $message_type = 'error';
                    }
                } catch (Throwable $e) {
                    $message = 'Unable to delete the weekly entry.';
                    $message_type = 'error';
                }
            }
        } else {
            $weekly_id = (int)($_POST['weekly_id'] ?? 0);
            $genre_id = (int)($_POST['genre_id'] ?? 0);
            $movie_name = trim($_POST['movie_name'] ?? '');
            $movie_year = (int)($_POST['movie_year'] ?? 2023);
            $movie_description = trim($_POST['movie_description'] ?? '');
            $movie_poster_url = trim($_POST['movie_poster_url'] ?? '');
            $movie_music_fit = trim($_POST['movie_music_fit'] ?? '');
            $character_name = trim($_POST['character_name'] ?? '');
            $character_show = trim($_POST['character_show'] ?? '');
            $character_description = trim($_POST['character_description'] ?? '');
            $character_traits = trim($_POST['character_traits'] ?? '');
            $character_music_match = trim($_POST['character_music_match'] ?? '');
            $character_image_url = trim($_POST['character_image_url'] ?? '');
            $week_start = trim($_POST['week_start'] ?? '');
            $week_end = trim($_POST['week_end'] ?? '');

            // Process image file uploads if supplied
            $uploadDir = __DIR__ . '/uploads/weekly';
            try {
                $uploadedPoster = handleSecureImageUpload('movie_poster_file', $uploadDir);
                if ($uploadedPoster !== null) {
                    $movie_poster_url = $uploadedPoster;
                }
                $uploadedCharImg = handleSecureImageUpload('character_image_file', $uploadDir);
                if ($uploadedCharImg !== null) {
                    $character_image_url = $uploadedCharImg;
                }
            } catch (Exception $ue) {
                $message = 'Upload Error: ' . $ue->getMessage();
                $message_type = 'error';
            }

            $validatedPoster = sanitizeAndValidateImageUrl($movie_poster_url);
            $validatedCharImg = sanitizeAndValidateImageUrl($character_image_url);

            $form = [
                'weekly_id' => $weekly_id,
                'genre_id' => $genre_id,
                'movie_name' => $movie_name,
                'movie_year' => $movie_year,
                'movie_description' => $movie_description,
                'movie_poster_url' => $movie_poster_url,
                'movie_music_fit' => $movie_music_fit,
                'character_name' => $character_name,
                'character_show' => $character_show,
                'character_description' => $character_description,
                'character_traits' => $character_traits,
                'character_music_match' => $character_music_match,
                'character_image_url' => $character_image_url,
                'week_start' => $week_start,
                'week_end' => $week_end
            ];

            if ($message_type !== 'error') {
                if ($genre_id <= 0 || $movie_name === '' || $movie_description === '' || $character_name === '' || $character_description === '' || $week_start === '' || $week_end === '') {
                    $message = 'Genre, movie name, movie synopsis, character name, character bio, and dates are required.';
                    $message_type = 'error';
                } elseif (strlen($movie_name) > 150 || strlen($character_name) > 150) {
                    $message = 'Movie name and character name must be 150 characters or fewer.';
                    $message_type = 'error';
                } elseif (!isValidIsoDate($week_start) || !isValidIsoDate($week_end)) {
                    $message = 'Please enter valid start and end dates.';
                    $message_type = 'error';
                } elseif ($week_start > $week_end) {
                    $message = 'Week start date cannot be after week end date.';
                    $message_type = 'error';
                } elseif (!empty($movie_poster_url) && $validatedPoster === null) {
                    $message = 'Movie poster URL must be a valid, legitimate HTTPS URL or uploaded image file.';
                    $message_type = 'error';
                } elseif (!empty($character_image_url) && $validatedCharImg === null) {
                    $message = 'Character image URL must be a valid, legitimate HTTPS URL or uploaded image file.';
                    $message_type = 'error';
                } else {
                    try {
                        $genreCheck = $conn->prepare("SELECT genre_id FROM genres WHERE genre_id = ? LIMIT 1");
                        $genreCheck->bind_param('i', $genre_id);
                        $genreCheck->execute();
                        $genreExists = $genreCheck->get_result()->fetch_assoc();
                        $genreCheck->close();

                        if (!$genreExists) {
                            $message = 'Selected genre does not exist.';
                            $message_type = 'error';
                        } else {
                            if ($weekly_id > 0) {
                                $overlapStmt = $conn->prepare(
                                    "SELECT weekly_id FROM weekly_movies
                                     WHERE genre_id = ? AND weekly_id <> ? AND week_start <= ? AND week_end >= ?
                                     LIMIT 1"
                                );
                                $overlapStmt->bind_param('iiss', $genre_id, $weekly_id, $week_end, $week_start);
                            } else {
                                $overlapStmt = $conn->prepare(
                                    "SELECT weekly_id FROM weekly_movies
                                     WHERE genre_id = ? AND week_start <= ? AND week_end >= ?
                                     LIMIT 1"
                                );
                                $overlapStmt->bind_param('iss', $genre_id, $week_end, $week_start);
                            }

                            $overlapStmt->execute();
                            $overlap = $overlapStmt->get_result()->fetch_assoc();
                            $overlapStmt->close();

                            if ($overlap) {
                                $message = 'An overlapping weekly recommendation already exists for this genre in that date range.';
                                $message_type = 'error';
                            } else {
                                if (empty($character_show)) {
                                    $character_show = $movie_name;
                                }
                                if (empty($character_traits)) {
                                    $character_traits = 'Creative, passionate, and resonant with musical emotion.';
                                }
                                if (empty($character_music_match)) {
                                    $character_music_match = 'This character shares your emotional resonance and love for genuine music expression.';
                                }
                                if (empty($movie_music_fit)) {
                                    $movie_music_fit = 'Features a stellar soundtrack that elevates every cinematic beat of your favorite genre.';
                                }

                                if ($weekly_id > 0) {
                                    $saveStmt = $conn->prepare(
                                        "UPDATE weekly_movies
                                         SET genre_id = ?, movie_name = ?, movie_year = ?, movie_description = ?, 
                                             movie_poster_url = ?, movie_music_fit = ?, character_name = ?, character_show = ?,
                                             character_description = ?, character_traits = ?, character_music_match = ?,
                                             character_image_url = ?, week_start = ?, week_end = ?
                                         WHERE weekly_id = ?"
                                    );
                                    $saveStmt->bind_param(
                                        'isisssssssssssi',
                                        $genre_id,
                                        $movie_name,
                                        $movie_year,
                                        $movie_description,
                                        $validatedPoster,
                                        $movie_music_fit,
                                        $character_name,
                                        $character_show,
                                        $character_description,
                                        $character_traits,
                                        $character_music_match,
                                        $validatedCharImg,
                                        $week_start,
                                        $week_end,
                                        $weekly_id
                                    );
                                    $saveStmt->execute();
                                    $saveStmt->close();
                                    $message = 'Weekly movie and character entry updated successfully.';
                                } else {
                                    $saveStmt = $conn->prepare(
                                        "INSERT INTO weekly_movies 
                                         (genre_id, movie_name, movie_year, movie_description, movie_poster_url, movie_music_fit,
                                          character_name, character_show, character_description, character_traits,
                                          character_music_match, character_image_url, week_start, week_end)
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                                    );
                                    $saveStmt->bind_param(
                                        'isisssssssssss',
                                        $genre_id,
                                        $movie_name,
                                        $movie_year,
                                        $movie_description,
                                        $validatedPoster,
                                        $movie_music_fit,
                                        $character_name,
                                        $character_show,
                                        $character_description,
                                        $character_traits,
                                        $character_music_match,
                                        $validatedCharImg,
                                        $week_start,
                                        $week_end
                                    );
                                    $saveStmt->execute();
                                    $saveStmt->close();
                                    $message = 'Weekly movie and character entry added successfully.';
                                }

                                $message_type = 'success';
                                $form = [
                                    'weekly_id' => 0,
                                    'genre_id' => 0,
                                    'movie_name' => '',
                                    'movie_year' => 2023,
                                    'movie_description' => '',
                                    'movie_poster_url' => '',
                                    'movie_music_fit' => '',
                                    'character_name' => '',
                                    'character_show' => '',
                                    'character_description' => '',
                                    'character_traits' => '',
                                    'character_music_match' => '',
                                    'character_image_url' => '',
                                    'week_start' => '',
                                    'week_end' => ''
                                ];
                            }
                        }
                    } catch (Throwable $e) {
                        $message = 'Unable to save the weekly entry: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
            }
        }
    }
} elseif (isset($_GET['edit_id'])) {
    $edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
    if ($edit_id !== false && $edit_id !== null && $edit_id > 0) {
        try {
            $editStmt = $conn->prepare(
                "SELECT weekly_id, genre_id, movie_name, movie_year, movie_description, movie_poster_url, movie_music_fit,
                        character_name, character_show, character_description, character_traits, character_music_match,
                        character_image_url, week_start, week_end
                 FROM weekly_movies
                 WHERE weekly_id = ?
                 LIMIT 1"
            );
            $editStmt->bind_param('i', $edit_id);
            $editStmt->execute();
            $editRow = $editStmt->get_result()->fetch_assoc();
            $editStmt->close();

            if ($editRow) {
                $form = [
                    'weekly_id' => (int)$editRow['weekly_id'],
                    'genre_id' => (int)$editRow['genre_id'],
                    'movie_name' => $editRow['movie_name'],
                    'movie_year' => (int)($editRow['movie_year'] ?? 2023),
                    'movie_description' => $editRow['movie_description'],
                    'movie_poster_url' => $editRow['movie_poster_url'] ?? '',
                    'movie_music_fit' => $editRow['movie_music_fit'] ?? '',
                    'character_name' => $editRow['character_name'],
                    'character_show' => $editRow['character_show'] ?? '',
                    'character_description' => $editRow['character_description'],
                    'character_traits' => $editRow['character_traits'] ?? '',
                    'character_music_match' => $editRow['character_music_match'] ?? '',
                    'character_image_url' => $editRow['character_image_url'] ?? '',
                    'week_start' => $editRow['week_start'],
                    'week_end' => $editRow['week_end']
                ];
            }
        } catch (Throwable $ignored) {
        }
    }
}

$genres = [];
try {
    $res = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $genres[] = $row;
        }
    }
} catch (Throwable $ignored) {
}

$entries = [];
try {
    $res = $conn->query(
        "SELECT wm.weekly_id, wm.genre_id, wm.movie_name, wm.movie_year, wm.movie_description, 
                wm.movie_poster_url, wm.movie_music_fit, wm.character_name, wm.character_show,
                wm.character_description, wm.character_traits, wm.character_music_match,
                wm.character_image_url, wm.week_start, wm.week_end, g.genre_name
         FROM weekly_movies wm
         INNER JOIN genres g ON wm.genre_id = g.genre_id
         ORDER BY wm.week_start DESC, g.genre_name ASC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $entries[] = $row;
        }
    }
} catch (Throwable $ignored) {
}

$csrf_token = getCsrfToken();
$is_editing = $form['weekly_id'] > 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Weekly &amp; Cinema Management</h1>
        <p class="page-subtitle">Publish, edit, and validate weekly cinema pairings, posters, and character matches.</p>
    </div>
    <a href="weekly_feature.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-eye"></i> View Live Feature
    </a>
</div>

<?php if ($message !== ''): ?>
    <div class="alert-box alert-<?= htmlspecialchars($message_type) ?>">
        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h2 style="font-size: 1.25rem; color: #fff; margin: 0;">
            <i class="fa-solid <?= $is_editing ? 'fa-pen-to-square' : 'fa-film' ?>"></i>
            <?= $is_editing ? 'Edit Weekly Pairing #' . (int)$form['weekly_id'] : 'Schedule New Weekly Pairing' ?>
        </h2>
        <span style="font-size: 0.8rem; color: var(--accent);"><i class="fa-solid fa-shield"></i> Authorized Admin Access</span>
    </div>

    <form method="POST" action="admin_weekly.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="weekly_id" value="<?= (int)$form['weekly_id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="genre_id">Target Music Genre *</label>
                <select id="genre_id" name="genre_id" class="form-control" required>
                    <option value="">Select Genre</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= (int)$genre['genre_id'] ?>" <?= (int)$form['genre_id'] === (int)$genre['genre_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($genre['genre_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="week_start">Active Week Start *</label>
                <input type="date" id="week_start" name="week_start" class="form-control" value="<?= htmlspecialchars($form['week_start']) ?>" required>
            </div>

            <div class="form-group">
                <label for="week_end">Active Week End *</label>
                <input type="date" id="week_end" name="week_end" class="form-control" value="<?= htmlspecialchars($form['week_end']) ?>" required>
            </div>
        </div>

        <!-- Section 1: Weekly Movie Details -->
        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-subtle);">
            <h3 style="font-size: 1.05rem; color: var(--forest-100); margin-bottom: 14px;">
                <i class="fa-solid fa-clapperboard"></i> 1. Weekly Movie Details
            </h3>

            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="movie_name">Movie Title *</label>
                    <input type="text" id="movie_name" name="movie_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($form['movie_name']) ?>" required placeholder="e.g. (500) Days of Summer">
                </div>

                <div class="form-group">
                    <label for="movie_year">Release Year *</label>
                    <input type="number" id="movie_year" name="movie_year" class="form-control" min="1900" max="2099" value="<?= (int)$form['movie_year'] ?>" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="movie_poster_url">Movie Poster HTTPS Image URL</label>
                    <input type="url" id="movie_poster_url" name="movie_poster_url" class="form-control" value="<?= htmlspecialchars($form['movie_poster_url']) ?>" placeholder="https://... or leave blank for upload">
                </div>

                <div class="form-group">
                    <label for="movie_poster_file">Or Upload Movie Poster File (JPG, PNG, WEBP)</label>
                    <input type="file" id="movie_poster_file" name="movie_poster_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                </div>
            </div>

            <div class="form-group">
                <label for="movie_description">Movie Synopsis *</label>
                <textarea id="movie_description" name="movie_description" class="form-control" rows="3" required placeholder="A concise, engaging synopsis of the film..."><?= htmlspecialchars($form['movie_description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="movie_music_fit">Why This Movie Fits The User's Music Preferences</label>
                <textarea id="movie_music_fit" name="movie_music_fit" class="form-control" rows="2" placeholder="Explain the musical soundtrack alignment with the listener's tastes..."><?= htmlspecialchars($form['movie_music_fit']) ?></textarea>
            </div>
        </div>

        <!-- Section 2: Character Details -->
        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-subtle);">
            <h3 style="font-size: 1.05rem; color: var(--forest-200); margin-bottom: 14px;">
                <i class="fa-solid fa-user-astronaut"></i> 2. Character of the Week Details
            </h3>

            <div class="form-grid">
                <div class="form-group">
                    <label for="character_name">Character Name *</label>
                    <input type="text" id="character_name" name="character_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($form['character_name']) ?>" required placeholder="e.g. Summer Finn">
                </div>

                <div class="form-group">
                    <label for="character_show">From Movie or Show</label>
                    <input type="text" id="character_show" name="character_show" class="form-control" maxlength="150" value="<?= htmlspecialchars($form['character_show']) ?>" placeholder="e.g. (500) Days of Summer">
                </div>

                <div class="form-group">
                    <label for="character_traits">Core Personality Traits</label>
                    <input type="text" id="character_traits" name="character_traits" class="form-control" value="<?= htmlspecialchars($form['character_traits']) ?>" placeholder="e.g. Independent, vintage vinyl enthusiast, witty">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="character_image_url">Character Image HTTPS URL</label>
                    <input type="url" id="character_image_url" name="character_image_url" class="form-control" value="<?= htmlspecialchars($form['character_image_url']) ?>" placeholder="https://... or leave blank for upload">
                </div>

                <div class="form-group">
                    <label for="character_image_file">Or Upload Character Portrait File (JPG, PNG, WEBP)</label>
                    <input type="file" id="character_image_file" name="character_image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                </div>
            </div>

            <div class="form-group">
                <label for="character_description">Character Bio &amp; Personality Description *</label>
                <textarea id="character_description" name="character_description" class="form-control" rows="3" required placeholder="Detailed description of the character's personality and demeanor..."><?= htmlspecialchars($form['character_description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="character_music_match">Why They Match The User's Music Personality</label>
                <textarea id="character_music_match" name="character_music_match" class="form-control" rows="2" placeholder="Describe the connection between their fictional vibe and the user's top-played genres..."><?= htmlspecialchars($form['character_music_match']) ?></textarea>
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                <i class="fa-solid <?= $is_editing ? 'fa-floppy-disk' : 'fa-plus' ?>"></i>
                <?= $is_editing ? 'Update Weekly Entry' : 'Publish Weekly Entry' ?>
            </button>
            <?php if ($is_editing): ?>
                <a href="admin_weekly.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="page-header" style="margin-top: 40px;">
    <h2 style="font-size: 1.25rem; color: #fff;">Scheduled Weekly Recommendations (<?= count($entries) ?> Total)</h2>
</div>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-film"></i>
        <h3>No Weekly Entries Yet</h3>
        <p>Use the form above to publish movie and character recommendations for each music genre.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Genre &amp; Window</th>
                    <th>Movie &amp; Music Fit</th>
                    <th>Character &amp; Match</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td style="width: 70px;">
                            <img src="<?= htmlspecialchars(!empty($entry['movie_poster_url']) ? $entry['movie_poster_url'] : 'assets/images/placeholder_movie.svg') ?>" 
                                 alt="Poster" 
                                 style="width: 54px; height: 75px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-subtle);"
                                 onerror="this.onerror=null; this.src='assets/images/placeholder_movie.svg';">
                        </td>
                        <td>
                            <span class="genre-tag" style="margin-top: 0;"><?= htmlspecialchars($entry['genre_name']) ?></span><br>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($entry['week_start']) ?> to <?= htmlspecialchars($entry['week_end']) ?></span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($entry['movie_name']) ?> (<?= (int)$entry['movie_year'] ?>)</strong><br>
                            <span style="color: var(--text-secondary); font-size: 0.82rem; line-height: 1.4; display: block; max-width: 320px;">
                                <?= htmlspecialchars(substr($entry['movie_description'], 0, 110)) ?>...
                            </span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($entry['character_name']) ?></strong> <span style="font-size: 0.8rem; color: var(--accent);">from <?= htmlspecialchars($entry['character_show'] ?? $entry['movie_name']) ?></span><br>
                            <span style="color: var(--text-secondary); font-size: 0.82rem; line-height: 1.4; display: block; max-width: 300px;">
                                <?= htmlspecialchars(substr($entry['character_traits'] ?? $entry['character_description'], 0, 100)) ?>...
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="admin_weekly.php?edit_id=<?= (int)$entry['weekly_id'] ?>" class="btn btn-secondary btn-sm" title="Edit Entry">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="admin_weekly.php" onsubmit="return confirm('Delete this weekly recommendation?');" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="weekly_id" value="<?= (int)$entry['weekly_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Entry">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
