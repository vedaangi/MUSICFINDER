<?php
require_once __DIR__ . '/includes/session_helper.php';
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif (strlen($username) > 50) {
        $message = "Username cannot exceed 50 characters.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or email is already taken. Please choose another.";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                $stmt->execute();

                $user_id = $conn->insert_id;

                try {
                    $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Registration', 'Account created successfully')");
                    if ($activity) {
                        $activity->bind_param("i", $user_id);
                        $activity->execute();
                        $activity->close();
                    }
                } catch (Throwable $ignored) {}

                $stmt->close();
                $conn->commit();

                $message = "Registration successful! You can now log in.";
                $message_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }
        }

        $check->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 480px; margin: 40px auto;">
    <div class="form-card" style="box-shadow: var(--shadow-card), var(--shadow-glow); border-color: var(--border-glow);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="playlist-icon" style="margin: 0 auto 14px auto; width: 56px; height: 56px; font-size: 26px;">
                <i class="fa-solid fa-heart"></i>
            </div>
            <h1 style="font-size: 1.7rem; margin: 0 0 6px 0; color: #fff;">Join VARP</h1>
            <p style="color: var(--text-secondary); margin: 0; font-size: 0.92rem;">Create your personalized VARP account</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $message_type ?>">
                <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($message_type === 'success'): ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" class="btn btn-primary" style="padding: 12px 28px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Proceed to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" class="form-control" maxlength="50" required placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="email"><i class="fa-solid fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="100" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="At least 6 characters" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fa-solid fa-lock-open"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Re-enter password" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px; padding: 12px;">
                    <i class="fa-solid fa-user-plus"></i> Create Free Account
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-subtle); font-size: 0.9rem; color: var(--text-secondary);">
            Already have an account? <a href="login.php" style="font-weight: 700;">Login here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
