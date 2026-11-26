
<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Music Finder</title>
</head>
<body>

    <h1>My Profile</h1>

    <p><strong>Username:</strong>
        <?php echo htmlspecialchars($user["username"]); ?>
    </p>

    <p><strong>Email:</strong>
        <?php echo htmlspecialchars($user["email"]); ?>
    </p>

    <p><strong>Account Created:</strong>
        <?php echo htmlspecialchars($user["created_at"]); ?>
    </p>

    <p><a href="update_profile.php">Update Profile</a></p>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

    <p><a href="logout.php">Logout</a></p>

</body>
</html>

<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Music Finder</title>
</head>
<body>

    <h1>My Profile</h1>

    <p><strong>Username:</strong>
        <?php echo htmlspecialchars($user["username"]); ?>
    </p>

    <p><strong>Email:</strong>
        <?php echo htmlspecialchars($user["email"]); ?>
    </p>

    <p><strong>Account Created:</strong>
        <?php echo htmlspecialchars($user["created_at"]); ?>
    </p>

    <p><a href="update_profile.php">Update Profile</a></p>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

    <p><a href="logout.php">Logout</a></p>

</body>
</html>

<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Music Finder</title>
</head>
<body>

    <h1>My Profile</h1>

    <p><strong>Username:</strong>
        <?php echo htmlspecialchars($user["username"]); ?>
    </p>

    <p><strong>Email:</strong>
        <?php echo htmlspecialchars($user["email"]); ?>
    </p>

    <p><strong>Account Created:</strong>
        <?php echo htmlspecialchars($user["created_at"]); ?>
    </p>

    <p><a href="update_profile.php">Update Profile</a></p>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

    <p><a href="logout.php">Logout</a></p>

</body>
</html>
