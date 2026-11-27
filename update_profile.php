<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();
$message = "";
$message_type = "";

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: logout.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if (empty($username) || empty($email)) {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif (strlen($username) > 50) {
        $message = "Username cannot exceed 50 characters.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
        $check->bind_param("ssi", $username, $email, $user_id);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Username or email is already taken by another account.";
            $message_type = "error";
        } else {
            $conn->begin_transaction();

            try {
                $update = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $update->bind_param("ssi", $username, $email, $user_id);
                $update->execute();

                try {
                    $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Profile Update', 'Username or email updated')");
                    if ($activity) {
                        $activity->bind_param("i", $user_id);
                        $activity->execute();
                        $activity->close();
                    }
                } catch (Throwable $e) {}

                $update->close();
                $conn->commit();

                $_SESSION["username"] = $username;
                $_SESSION["email"] = $email;

                $user["username"] = $username;
                $user["email"] = $email;

                $message = "Your profile has been updated successfully.";
                $message_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Unable to update profile. Please try again.";
                $message_type = "error";
            }
        }

        $check->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Account Profile</h1>
        <p class="page-subtitle">Update your display username and primary email address.</p>
    </div>
    <a href="profile.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Profile
    </a>
</div>

<div style="max-width: 540px; margin: 20px 0;">
    <div class="form-card">
        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $message_type ?>">
                <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="update_profile.php">
            <div class="form-group">
                <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user["username"]) ?>" maxlength="50" required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fa-solid fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user["email"]) ?>" maxlength="100" required>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
