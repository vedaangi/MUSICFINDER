<?php
require_once __DIR__ . '/includes/session_helper.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check for existing username or email
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with that username or email already exists.";
            $check->close();
        } else {
            $check->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hash);

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $stmt->close();
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                header("Location: index.php?welcome=1");
                exit();
            } else {
                $error = "Failed to create account: " . $conn->error;
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MusicFinder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <div class="auth-card" style="width: 100%; max-width: 460px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 32px; box-shadow: var(--shadow);">
        
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="display: inline-flex; align-items: center; gap: 10px; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <i class="fa-solid fa-compact-disc" style="color: var(--accent);"></i>
                <span>MusicFinder</span>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Create your free account to curate playlists and get personalized music recommendations.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="padding: 12px 14px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: var(--radius); color: #b91c1c; font-size: 0.88rem; margin-bottom: 18px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password (min 6 characters)</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
            </div>

            <button type="submit" name="register_submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Create Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 0.88rem; color: var(--text-secondary);">
            Already have an account? <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Sign in here</a>
        </div>

    </div>

</body>
</html>
