<?php
require_once __DIR__ . '/check_subscription.php';
$admin_user_id = requireAdmin($conn);

function isValidIsoDate(string $value): bool
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $dt !== false && $dt->format('Y-m-d') === $value;
}

$message = '';
$message_type = '';

$form = [
    'weekly_id' => 0,
    'genre_id' => 0,
    'movie_name' => '',
    'movie_description' => '',
    'character_name' => '',
    'character_description' => '',
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
                        $message = 'Weekly entry deleted successfully.';
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
            $movie_description = trim($_POST['movie_description'] ?? '');
            $character_name = trim($_POST['character_name'] ?? '');
            $character_description = trim($_POST['character_description'] ?? '');
            $week_start = trim($_POST['week_start'] ?? '');
            $week_end = trim($_POST['week_end'] ?? '');

            $form = [
                'weekly_id' => $weekly_id,
                'genre_id' => $genre_id,
                'movie_name' => $movie_name,
                'movie_description' => $movie_description,
                'character_name' => $character_name,
                'character_description' => $character_description,
                'week_start' => $week_start,
                'week_end' => $week_end
            ];

            if ($genre_id <= 0 || $movie_name === '' || $movie_description === '' || $character_name === '' || $character_description === '' || $week_start === '' || $week_end === '') {
                $message = 'All fields are required.';
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
                            if ($weekly_id > 0) {
                                $saveStmt = $conn->prepare(
                                    "UPDATE weekly_movies
                                     SET genre_id = ?, movie_name = ?, movie_description = ?, character_name = ?, character_description = ?, week_start = ?, week_end = ?
                                     WHERE weekly_id = ?"
                                );
                                $saveStmt->bind_param(
                                    'issssssi',
                                    $genre_id,
                                    $movie_name,
                                    $movie_description,
                                    $character_name,
                                    $character_description,
                                    $week_start,
                                    $week_end,
                                    $weekly_id
                                );
                                $saveStmt->execute();
                                $saveStmt->close();
                                $message = 'Weekly movie and character entry updated successfully.';
                            } else {
                                $saveStmt = $conn->prepare(
                                    "INSERT INTO weekly_movies (genre_id, movie_name, movie_description, character_name, character_description, week_start, week_end)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                                );
                                $saveStmt->bind_param(
                                    'issssss',
                                    $genre_id,
                                    $movie_name,
                                    $movie_description,
                                    $character_name,
                                    $character_description,
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
                                'movie_description' => '',
                                'character_name' => '',
                                'character_description' => '',
                                'week_start' => '',
                                'week_end' => ''
                            ];
                        }
                    }
                } catch (Throwable $e) {
                    $message = 'Unable to save the weekly entry. Please check your database tables and try again.';
                    $message_type = 'error';
                }
            }
        }
    }
} elseif (isset($_GET['edit_id'])) {
    $edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
    if ($edit_id !== false && $edit_id !== null && $edit_id > 0) {
        try {
            $editStmt = $conn->prepare(
                "SELECT weekly_id, genre_id, movie_name, movie_description, character_name, character_description, week_start, week_end
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
                    'movie_description' => $editRow['movie_description'],
                    'character_name' => $editRow['character_name'],
                    'character_description' => $editRow['character_description'],
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
        "SELECT wm.weekly_id, wm.genre_id, wm.movie_name, wm.movie_description, wm.character_name, wm.character_description, wm.week_start, wm.week_end, g.genre_name
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
        <h1 class="page-title">Admin Weekly Management</h1>
        <p class="page-subtitle">Add, edit or remove weekly movie and character recommendations by genre.</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert-box alert-<?= htmlspecialchars($message_type) ?>">
        <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="form-card">
    <h2 style="font-size: 1.2rem; color: #fff; margin-bottom: 18px;">
        <?= $is_editing ? 'Edit Weekly Recommendation #' . (int)$form['weekly_id'] : 'Add Weekly Recommendation' ?>
    </h2>

    <form method="POST" action="admin_weekly.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="weekly_id" value="<?= (int)$form['weekly_id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="genre_id">Genre</label>
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
                <label for="week_start">Week Start</label>
                <input type="date" id="week_start" name="week_start" class="form-control" value="<?= htmlspecialchars($form['week_start']) ?>" required>
            </div>

            <div class="form-group">
                <label for="week_end">Week End</label>
                <input type="date" id="week_end" name="week_end" class="form-control" value="<?= htmlspecialchars($form['week_end']) ?>" required>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="movie_name">Movie Name</label>
                <input type="text" id="movie_name" name="movie_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($form['movie_name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="character_name">Character Name</label>
                <input type="text" id="character_name" name="character_name" class="form-control" maxlength="150" value="<?= htmlspecialchars($form['character_name']) ?>" required>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="movie_description">Movie Description</label>
                <textarea id="movie_description" name="movie_description" class="form-control" rows="3" required><?= htmlspecialchars($form['movie_description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="character_description">Character Description</label>
                <textarea id="character_description" name="character_description" class="form-control" rows="3" required><?= htmlspecialchars($form['character_description']) ?></textarea>
            </div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid <?= $is_editing ? 'fa-floppy-disk' : 'fa-plus' ?>"></i>
                <?= $is_editing ? 'Update Weekly Entry' : 'Add Weekly Entry' ?>
            </button>
            <?php if ($is_editing): ?>
                <a href="admin_weekly.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="page-header">
    <h2 style="font-size: 1.25rem; color: #fff;">Scheduled Weekly Recommendations</h2>
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
                    <th>Genre</th>
                    <th>Week Window</th>
                    <th>Movie</th>
                    <th>Character</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><span class="genre-tag" style="margin-top: 0;"><?= htmlspecialchars($entry['genre_name']) ?></span></td>
                        <td><?= htmlspecialchars($entry['week_start']) ?> to <?= htmlspecialchars($entry['week_end']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($entry['movie_name']) ?></strong><br>
                            <span style="color: var(--text-secondary); font-size: 0.82rem;"><?= htmlspecialchars($entry['movie_description']) ?></span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($entry['character_name']) ?></strong><br>
                            <span style="color: var(--text-secondary); font-size: 0.82rem;"><?= htmlspecialchars($entry['character_description']) ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="admin_weekly.php?edit_id=<?= (int)$entry['weekly_id'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <form method="POST" action="admin_weekly.php" onsubmit="return confirm('Delete this weekly entry?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="weekly_id" value="<?= (int)$entry['weekly_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa-solid fa-trash"></i> Delete
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
