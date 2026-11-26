<?php
require_once __DIR__ . '/check_subscription.php';

$user_id = requireLogin();
$message = '';
$message_type = '';

if (!empty($_SESSION['subscription_notice'])) {
    $message = $_SESSION['subscription_notice'];
    $message_type = 'info';
    unset($_SESSION['subscription_notice']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please refresh and try again.';
        $message_type = 'error';
    } else {
        $plan = 'Music Finder Premium';
        $today = date('Y-m-d');
        $end_date = (new DateTimeImmutable($today))->modify('+1 month')->format('Y-m-d');

        $conn->begin_transaction();

        try {
            $dupStmt = $conn->prepare(
                "SELECT subscription_id, end_date
                 FROM subscriptions
                 WHERE user_id = ? AND status = 'active' AND end_date >= ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $dupStmt->bind_param('is', $user_id, $today);
            $dupStmt->execute();
            $existing = $dupStmt->get_result()->fetch_assoc();
            $dupStmt->close();

            if ($existing) {
                $conn->rollback();
                $message = 'You already have an active subscription valid until ' . $existing['end_date'] . '.';
                $message_type = 'error';
            } else {
                $expireOld = $conn->prepare(
                    "UPDATE subscriptions
                     SET status = 'expired'
                     WHERE user_id = ? AND status = 'active' AND end_date < ?"
                );
                $expireOld->bind_param('is', $user_id, $today);
                $expireOld->execute();
                $expireOld->close();

                $insertStmt = $conn->prepare(
                    "INSERT INTO subscriptions (user_id, plan, start_date, end_date, status)
                     VALUES (?, ?, ?, ?, 'active')"
                );
                $insertStmt->bind_param('isss', $user_id, $plan, $today, $end_date);
                $insertStmt->execute();
                $insertStmt->close();

                $conn->commit();

                try {
                    $actStmt = $conn->prepare(
                        "INSERT INTO user_activity (user_id, activity_type, description)
                         VALUES (?, 'Subscription', 'Activated Music Finder Premium for 1 month')"
                    );
                    if ($actStmt) {
                        $actStmt->bind_param('i', $user_id);
                        $actStmt->execute();
                        $actStmt->close();
                    }
                } catch (Throwable $ignored) {
                }

                $message = 'Music Finder Premium activated! Your subscription expires on ' . $end_date . '.';
                $message_type = 'success';
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $message = 'Unable to activate subscription right now. Please try again.';
            $message_type = 'error';
        }
    }
}

$subscription = getUserSubscription($conn, $user_id);
$is_active = ($subscription !== null && $subscription['is_active'] === true);
$csrf_token = getCsrfToken();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Music Finder Premium</h1>
        <p class="page-subtitle">Upgrade your account to unlock analytics, personality insights, weekly features and eligible downloads.</p>
    </div>
</div>

<div class="premium-plan-wrapper">
    <?php if ($message !== ''): ?>
        <div class="alert-box alert-<?= htmlspecialchars($message_type) ?>">
            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : ($message_type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="premium-plan-card">
        <span class="premium-badge"><i class="fa-solid fa-crown"></i> Premium Plan</span>
        <h2 style="font-size: 1.65rem; color: #fff;">Music Finder Premium</h2>
        <div class="premium-price">₹99 <span>/ month</span></div>
        <p style="color: var(--text-secondary); font-size: 0.92rem;">Unlock additional music discovery features:</p>

        <ul class="feature-list">
            <li><i class="fa-solid fa-check"></i> Top artist analytics</li>
            <li><i class="fa-solid fa-check"></i> Advanced recommendations</li>
            <li><i class="fa-solid fa-check"></i> Weekly movie and character results</li>
            <li><i class="fa-solid fa-check"></i> Eligible music downloads</li>
        </ul>

        <?php if ($is_active): ?>
            <div class="alert-box alert-success" style="margin-bottom: 18px;">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Active Subscription</strong><br>
                    <span>Plan: <?= htmlspecialchars($subscription['plan']) ?> | Expires on: <?= htmlspecialchars($subscription['end_date']) ?></span>
                </div>
            </div>
            <a href="premium_dashboard.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-crown"></i> Open Premium Dashboard
            </a>
        <?php else: ?>
            <?php if ($subscription !== null && $subscription['status'] === 'expired'): ?>
                <div class="alert-box alert-info" style="margin-bottom: 18px;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Previous subscription expired on <?= htmlspecialchars($subscription['end_date']) ?>.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="subscribe.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-bolt"></i> Subscribe Now
                </button>
            </form>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 12px; text-align: center;">
                Prototype activation: activates a 1-month subscription with no real payment collected.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
