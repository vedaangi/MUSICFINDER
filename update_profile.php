
<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$message = "";

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);

    if (empty($username) || empty($email)) {
        $message = "All fields are required.";
    } elseif (strlen($username) > 50) {
        $message = "Username cannot exceed 50 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $message = "Please enter a valid email address.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check->bind_param("ssi", $username, $email, $user_id);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Username or email is already taken.";
        } else {
            $conn->begin_transaction();

            try {
                $update = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $update->bind_param("ssi", $username, $email, $user_id);
                $update->execute();

                $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Profile Update', 'Username or email updated')");
                $activity->bind_param("i", $user_id);
                $activity->execute();

                $activity->close();
                $update->close();

                $conn->commit();

                $_SESSION["username"] = $username;
                $_SESSION["email"] = $email;

                $user["username"] = $username;
                $user["email"] = $email;

                $message = "Profile updated successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Unable to update profile. Please try again.";
            }
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Profile - Music Finder</title>
</head>
<body>

    <h1>Update Profile</h1>

    <?php if (!empty($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" action="update_profile.php">

        <label>Username:</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user["username"]); ?>" maxlength="50" required>
        <br><br>

        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user["email"]); ?>" maxlength="100" required>
        <br><br>

        <button type="submit">Save Changes</button>

    </form>

    <p><a href="profile.php">Back to Profile</a></p>
    <p><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>
