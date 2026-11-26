<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$message = "";

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

        $activity = @$conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Preferences Update', 'Music preferences saved')");
        if ($activity) {
            $activity->bind_param("i", $user_id);
            $activity->execute();
            $activity->close();
        }

        $conn->commit();
        $message = "Your preferences have been saved successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Unable to save your preferences. Please try again.";
    }
}

$genres = $conn->query("SELECT genre_id, genre_name FROM genres ORDER BY genre_name");

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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Music Preferences - Music Finder</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <h1>Your Music Preferences</h1>
    <p>Select the genres you enjoy listening to.</p>

    <?php if (!empty($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" action="preferences.php">
        <?php if ($genres && $genres->num_rows > 0): ?>
            <?php while ($genre = $genres->fetch_assoc()): ?>
                <label>
                    <input
                        type="checkbox"
                        name="genre_ids[]"
                        value="<?php echo (int)$genre["genre_id"]; ?>"
                        <?php echo in_array((int)$genre["genre_id"], $selected_genres, true) ? "checked" : ""; ?>
                    >
                    <?php echo htmlspecialchars($genre["genre_name"]); ?>
                </label>
                <br><br>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No genres are available yet.</p>
        <?php endif; ?>

        <button type="submit">Save Preferences</button>
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>
