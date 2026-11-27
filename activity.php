<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();

$stmt = $conn->prepare("SELECT activity_type, description, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$result = null;
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Account Activity</h1>
        <p class="page-subtitle">A transparent timeline of your logins, updates, and account actions.</p>
    </div>
    <a href="profile.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Profile
    </a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Details</th>
                    <th>Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($activity = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="status-pill status-active" style="background: rgba(168, 85, 247, 0.2); border-color: rgba(168, 85, 247, 0.4); color: var(--text-primary);">
                                <?= htmlspecialchars($activity["activity_type"]) ?>
                            </span>
                        </td>
                        <td style="color: var(--text-primary); font-size: 0.92rem;"><?= htmlspecialchars($activity["description"] ?? "") ?></td>
                        <td style="color: var(--text-secondary); font-size: 0.85rem;"><?= htmlspecialchars($activity["created_at"]) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fa-solid fa-wave-square"></i>
        <h3>No Account Activity Recorded Yet</h3>
        <p>Your future logins, subscription changes, and profile updates will be logged here.</p>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
<?php endif; ?>

<?php
if ($stmt) $stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
