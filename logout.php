
<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION["user_id"])) {
    $user_id = (int)$_SESSION["user_id"];

    $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Logout', 'User logged out successfully')");
    $activity->bind_param("i", $user_id);
    $activity->execute();
    $activity->close();
}

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;
?>

<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION["user_id"])) {
    $user_id = (int)$_SESSION["user_id"];

    $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Logout', 'User logged out successfully')");
    $activity->bind_param("i", $user_id);
    $activity->execute();
    $activity->close();
}

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;
?>

<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION["user_id"])) {
    $user_id = (int)$_SESSION["user_id"];

    $activity = $conn->prepare("INSERT INTO user_activity (user_id, activity_type, description) VALUES (?, 'Logout', 'User logged out successfully')");
    $activity->bind_param("i", $user_id);
    $activity->execute();
    $activity->close();
}

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;
?>
