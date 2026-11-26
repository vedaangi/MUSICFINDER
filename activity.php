
<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare("SELECT activity_type, description, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account Activity - Music Finder</title>
</head>
<body>

    <h1>My Account Activity</h1>

    <p>Here you can view your recent account activities.</p>

    <?php if ($result->num_rows > 0): ?>

        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>Activity</th>
                <th>Description</th>
                <th>Date and Time</th>
            </tr>

            <?php while ($activity = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activity["activity_type"]); ?></td>
                    <td><?php echo htmlspecialchars($activity["description"] ?? ""); ?></td>
                    <td><?php echo htmlspecialchars($activity["created_at"]); ?></td>
                </tr>
            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p>No account activity found yet.</p>

    <?php endif; ?>

    <?php $stmt->close(); ?>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>

<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare("SELECT activity_type, description, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account Activity - Music Finder</title>
</head>
<body>

    <h1>My Account Activity</h1>

    <p>Here you can view your recent account activities.</p>

    <?php if ($result->num_rows > 0): ?>

        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>Activity</th>
                <th>Description</th>
                <th>Date and Time</th>
            </tr>

            <?php while ($activity = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activity["activity_type"]); ?></td>
                    <td><?php echo htmlspecialchars($activity["description"] ?? ""); ?></td>
                    <td><?php echo htmlspecialchars($activity["created_at"]); ?></td>
                </tr>
            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p>No account activity found yet.</p>

    <?php endif; ?>

    <?php $stmt->close(); ?>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>

<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare("SELECT activity_type, description, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account Activity - Music Finder</title>
</head>
<body>

    <h1>My Account Activity</h1>

    <p>Here you can view your recent account activities.</p>

    <?php if ($result->num_rows > 0): ?>

        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>Activity</th>
                <th>Description</th>
                <th>Date and Time</th>
            </tr>

            <?php while ($activity = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activity["activity_type"]); ?></td>
                    <td><?php echo htmlspecialchars($activity["description"] ?? ""); ?></td>
                    <td><?php echo htmlspecialchars($activity["created_at"]); ?></td>
                </tr>
            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p>No account activity found yet.</p>

    <?php endif; ?>

    <?php $stmt->close(); ?>

    <p><a href="dashboard.php">Back to Dashboard</a></p>

</body>
</html>
