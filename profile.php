<?php
require_once __DIR__ . '/includes/session_helper.php';
requireLogin();

require_once __DIR__ . '/config/db.php';
$user_id = getCurrentUserId();

$stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: logout.php");
    exit;
}

$today = date('Y-m-d');
$subStmt = $conn->prepare("SELECT plan, start_date, end_date, status FROM subscriptions WHERE user_id = ? ORDER BY end_date DESC LIMIT 1");
$subStmt->bind_param("i", $user_id);
$subStmt->execute();
$sub = $subStmt->get_result()->fetch_assoc();
$subStmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">My Account Profile</h1>
        <p class="page-subtitle">View and manage your personal credentials, subscription and preferences.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="update_profile.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-pen"></i> Edit Profile
        </a>
        <a href="preferences.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-sliders"></i> Preferences
        </a>
    </div>
</div>

<div class="two-col-grid" style="margin-bottom: 28px;">
    <div class="form-card">
        <h2 style="font-size: 1.25rem; color: #fff; margin-bottom: 18px;">
            <i class="fa-solid fa-id-card" style="color: var(--forest-200); margin-right: 8px;"></i>
            Account Details
        </h2>
        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div>
                <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Username</span>
                <p style="margin: 2px 0 0; font-size: 1.05rem; font-weight: 700; color: #fff;"><?= htmlspecialchars($user['username']) ?></p>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Email Address</span>
                <p style="margin: 2px 0 0; font-size: 1.05rem; font-weight: 600; color: #fff;"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Account Role</span>
                <p style="margin: 2px 0 0; font-size: 0.95rem; font-weight: 600; color: var(--forest-200);">
                    <?= ucfirst(htmlspecialchars($user['role'] ?? 'user')) ?>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <span style="font-size: 0.8rem; background: rgba(33, 55, 34, 0.6); color: var(--forest-100); padding: 2px 8px; border-radius: 999px; margin-left: 6px; border: 1px solid var(--border-forest);">Administrator</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Member Since</span>
                <p style="margin: 2px 0 0; font-size: 0.95rem; color: var(--text-secondary);"><?= htmlspecialchars($user['created_at']) ?></p>
            </div>
        </div>
    </div>

    <div class="form-card">
        <h2 style="font-size: 1.25rem; color: #fff; margin-bottom: 18px;">
            <i class="fa-solid fa-crown" style="color: var(--warning); margin-right: 8px;"></i>
            Subscription Status
        </h2>
        <?php if ($sub && $sub['status'] === 'active' && $sub['end_date'] >= $today): ?>
            <div class="alert-box alert-success" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Active Premium Plan</strong><br>
                    <span><?= htmlspecialchars($sub['plan']) ?></span>
                </div>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 16px;">
                Started on <strong><?= htmlspecialchars($sub['start_date']) ?></strong> &bull;
                Valid until <strong><?= htmlspecialchars($sub['end_date']) ?></strong>
            </p>
            <a href="premium_dashboard.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-gem"></i> Go to Premium Hub
            </a>
        <?php else: ?>
            <div class="alert-box alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong>Standard Free Account</strong><br>
                    <span>Upgrade to VARP Premium for ₹99/month.</span>
                </div>
            </div>
            <p style="color: var(--text-secondary); font-size: 0.92rem; margin-bottom: 20px;">
                Unlock top artists analytics, music personality match, weekly movie features and eligible audio track downloads.
            </p>
            <a href="subscribe.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-bolt"></i> Upgrade to Premium (₹99)
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="page-header">
    <h2 style="font-size: 1.2rem; color: #fff; margin: 0;">Quick Navigation</h2>
</div>

<div style="display: flex; gap: 12px; flex-wrap: wrap;">
    <a href="playlists.php" class="btn btn-secondary"><i class="fa-solid fa-list-ul"></i> My Playlists</a>
    <a href="history.php" class="btn btn-secondary"><i class="fa-solid fa-clock-rotate-left"></i> Listening History</a>
    <a href="activity.php" class="btn btn-secondary"><i class="fa-solid fa-wave-square"></i> Activity Log</a>
    <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
