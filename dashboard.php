
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"] ?? "User";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Music Finder</title>
</head>
<body>

    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

    <p>Welcome to your Music Finder dashboard.</p>

    <h2>Your Account</h2>

    <p><a href="profile.php">View Profile</a></p>
    <p><a href="update_profile.php">Account Settings</a></p>
    <p><a href="preferences.php">My Music Preferences</a></p>
    <p><a href="activity.php">View Account Activity</a></p>

    <h2>Music Discovery</h2>

    <p><a href="index.php">Explore Songs</a></p>

    <h2>Account</h2>

    <p><a href="logout.php">Logout</a></p>

</body>
</html>

<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"] ?? "User";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Music Finder</title>
</head>
<body>

    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

    <p>Welcome to your Music Finder dashboard.</p>

    <h2>Your Account</h2>

    <p><a href="profile.php">View Profile</a></p>
    <p><a href="update_profile.php">Account Settings</a></p>
    <p><a href="preferences.php">My Music Preferences</a></p>
    <p><a href="activity.php">View Account Activity</a></p>

    <h2>Music Discovery</h2>

    <p><a href="index.php">Explore Songs</a></p>

    <h2>Account</h2>

    <p><a href="logout.php">Logout</a></p>

</body>
</html>

<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"] ?? "User";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Music Finder</title>
</head>
<body>

    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

    <p>Welcome to your Music Finder dashboard.</p>

    <h2>Your Account</h2>

    <p><a href="profile.php">View Profile</a></p>
    <p><a href="update_profile.php">Account Settings</a></p>
    <p><a href="preferences.php">My Music Preferences</a></p>
    <p><a href="activity.php">View Account Activity</a></p>

    <h2>Music Discovery</h2>

    <p><a href="index.php">Explore Songs</a></p>

    <h2>Account</h2>

    <p><a href="logout.php">Logout</a></p>

</body>
</html>
