
<?php
require_once 'config/db.php';

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

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
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or email already exists.";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                $stmt->execute();

                $user_id = $conn->insert_id;

                $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Registration', 'Account created successfully')");
                $activity->bind_param("i", $user_id);
                $activity->execute();

                $activity->close();
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Music Finder</title>
</head>
<body>

    <h1>Create Your Account</h1>

    <?php if (!empty($message)): ?>
        <p style="color: <?php echo $message_type === 'success' ? 'green' : 'red'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <label>Username:</label>
        <input type="text" name="username" maxlength="50" required>
        <br><br>

        <label>Email:</label>
        <input type="email" name="email" maxlength="100" required>
        <br><br>

        <label>Password:</label>
        <input type="password" name="password" required>
        <br><br>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>
        <br><br>

        <button type="submit">Register</button>

    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
    <p><a href="index.php">Back to Music Finder</a></p>

</body>
</html>
