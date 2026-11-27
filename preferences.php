<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected_genres = $_POST["genre_ids"] ?? [];

    if (!is_array($selected_genres)) {
        $selected_genres = [];
    }

    $selected_genres = array_unique(array_filter(
        $selected_genres,
        function ($id) {
            return filter_var($id, FILTER_VALIDATE_INT) !== false && (int)$id > 0;
        }
    ));

    $conn->begin_transaction();

    try {
        $delete = $conn->prepare("DELETE FROM user_preferences WHERE user_id = ?");
        if ($delete) {
            $delete->bind_param("i", $user_id);
            $delete->execute();
            $delete->close();
        }

        $insert = $conn->prepare("INSERT INTO user_preferences (user_id, genre_id) SELECT ?, genre_id FROM genres WHERE genre_id = ?");
        if ($insert) {
            foreach ($selected_genres as $genre_id) {
                $genre_id = (int)$genre_id;
                $insert->bind_param("ii", $user_id, $genre_id);
                $insert->execute();
            }
            $insert->close();
        }

        try {
            $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Preferences Update', 'Music preferences saved')");
            if ($activity) {
                $activity->bind_param("i", $user_id);
                $activity->execute();
                $activity->close();
            }
        } catch (Throwable $e) {}

        $conn->commit();
        $message = "Your genre preferences have been saved successfully.";
        $message_type = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Unable to save your preferences. Please try again.";
        $message_type = "error";
    }
}

$genres = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name ASC");

$stmt = $conn->prepare("SELECT genre_id FROM user_preferences WHERE user_id = ?");
$selected_genres = [];
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $selected_genres[] = (int)$row["genre_id"];
    }
    $stmt->close();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Favorite Music Genres</h1>
        <p class="page-subtitle">Select the musical styles you enjoy most to guide your recommendations.</p>
    </div>
    <a href="profile.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Profile
    </a>
</div>

<div class="form-card" style="max-width: 700px;">
    <?php if (!empty($message)): ?>
        <div class="alert-box alert-<?= $message_type ?>">
            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="preferences.php">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <?php if ($genres && $genres->num_rows > 0): ?>
                <?php while ($genre = $genres->fetch_assoc()): 
                    $gid = (int)$genre["genre_id"];
                    $checked = in_array($gid, $selected_genres, true);
                ?>
                    <label style="background: var(--bg-surface); border: 1px solid <?= $checked ? 'var(--forest-200)' : 'var(--border-subtle)' ?>; border-radius: var(--radius-md); padding: 12px 14px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s ease;">
                        <input
                            type="checkbox"
                            name="genre_ids[]"
                            value="<?= $gid ?>"
                            <?= $checked ? "checked" : "" ?>
                            style="width: auto; margin: 0; accent-color: var(--forest-200);"
                        >
                        <span style="font-weight: 600; color: #fff; font-size: 0.92rem;"><?= htmlspecialchars($genre["genre_name"]) ?></span>
                    </label>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--text-muted);">No genres available in the database.</p>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Preferences
            </button>
            <a href="recommendations.php" class="btn btn-secondary">
                <i class="fa-solid fa-sparkles"></i> View Recommendations
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
