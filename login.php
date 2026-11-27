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
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"] ?? 'user';

                try {
                    $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Login', 'User logged in successfully')");
                    if ($activity) {
                        $activity->bind_param("i", $user["id"]);
                        $activity->execute();
                        $activity->close();
                    }
                } catch (Throwable $e) {}

                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Incorrect email or password.";
                $message_type = "error";
            }
        } else {
            $message = "Incorrect email or password.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 460px; margin: 40px auto;">
    <div class="form-card" style="box-shadow: var(--shadow-card), var(--shadow-glow); border-color: var(--border-glow);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="playlist-icon" style="margin: 0 auto 14px auto; width: 56px; height: 56px; font-size: 26px;">
                <i class="fa-solid fa-sparkles"></i>
            </div>
            <h1 style="font-size: 1.7rem; margin: 0 0 6px 0; color: #fff;">Welcome Back</h1>
            <p style="color: var(--text-secondary); margin: 0; font-size: 0.92rem;">Sign in to your VARP workspace</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $message_type ?>">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email"><i class="fa-solid fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password" autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px; padding: 12px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-subtle); font-size: 0.9rem; color: var(--text-secondary);">
            Don't have an account? <a href="register.php" style="font-weight: 700;">Join Free</a>
        </div>
        <div style="text-align: center; margin-top: 10px; font-size: 0.85rem; color: var(--text-dim);">
            Demo account: <code>alex@example.com</code> / <code>password123</code> (Admin &amp; Premium)
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
