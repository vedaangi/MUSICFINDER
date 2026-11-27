<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

if (!function_exists('requireLogin')) {
    function requireLogin(bool $jsonResponse = false): int
    {
        if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
            if ($jsonResponse) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Authentication required.'
                ]);
                exit;
            }
            header('Location: login.php');
            exit;
        }

        return (int)$_SESSION['user_id'];
    }
}

if (!function_exists('getCsrfToken')) {
    function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || !is_string($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

function getUserSubscription(mysqli $conn, int $user_id): ?array
{
    try {
        $stmt = $conn->prepare(
            "SELECT subscription_id, user_id, plan, start_date, end_date, status
             FROM subscriptions
             WHERE user_id = ?
             ORDER BY end_date DESC, subscription_id DESC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $subscription = $result->fetch_assoc();
        $stmt->close();

        if (!$subscription) {
            return null;
        }

        $today = date('Y-m-d');

        if ($subscription['status'] === 'active' && $subscription['end_date'] < $today) {
            $update = $conn->prepare(
                "UPDATE subscriptions SET status = 'expired' WHERE subscription_id = ?"
            );
            if ($update) {
                $sub_id = (int)$subscription['subscription_id'];
                $update->bind_param('i', $sub_id);
                $update->execute();
                $update->close();
            }
            $subscription['status'] = 'expired';
        }

        $subscription['subscription_id'] = (int)$subscription['subscription_id'];
        $subscription['user_id'] = (int)$subscription['user_id'];
        $subscription['is_active'] = ($subscription['status'] === 'active' && $subscription['end_date'] >= $today);

        return $subscription;
    } catch (Throwable $e) {
        return null;
    }
}

function hasActiveSubscription(mysqli $conn, int $user_id): bool
{
    $subscription = getUserSubscription($conn, $user_id);
    return $subscription !== null && $subscription['is_active'] === true;
}

function requirePremiumSubscription(mysqli $conn, bool $jsonResponse = false): array
{
    $user_id = requireLogin($jsonResponse);
    $subscription = getUserSubscription($conn, $user_id);

    if ($subscription === null || $subscription['is_active'] !== true) {
        if ($subscription !== null && $subscription['status'] === 'expired') {
            $notice = 'Your VARP Premium subscription expired on ' . $subscription['end_date'] . '. Renew to continue accessing premium features.';
        } else {
            $notice = 'An active VARP Premium subscription is required to access this feature.';
        }

        if ($jsonResponse) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => $notice
            ]);
            exit;
        }

        $_SESSION['subscription_notice'] = $notice;
        header('Location: subscribe.php');
        exit;
    }

    return $subscription;
}

function isAdminUser(mysqli $conn, int $user_id): bool
{
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $role = $row['role'] ?? 'user';
        $_SESSION['role'] = $role;

        return $role === 'admin';
    } catch (Throwable $e) {
        return false;
    }
}

function requireAdmin(mysqli $conn): int
{
    $user_id = requireLogin();

    if (!isAdminUser($conn, $user_id)) {
        http_response_code(403);
        require_once __DIR__ . '/includes/header.php';
        ?>
        <div class="empty-state">
            <i class="fa-solid fa-shield-halved" style="color: var(--danger);"></i>
            <h3>Administrator Access Required</h3>
            <p>You do not have permission to view or manage weekly recommendations.</p>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
        <?php
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    return $user_id;
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $user_id = requireLogin(true);
    $subscription = getUserSubscription($conn, $user_id);
    $is_active = $subscription !== null && $subscription['is_active'] === true;

    if (isset($_GET['redirect']) && $_GET['redirect'] === '1') {
        header('Location: ' . ($is_active ? 'premium_dashboard.php' : 'subscribe.php'));
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'user_id' => $user_id,
        'has_active_subscription' => $is_active,
        'subscription' => $subscription
    ]);
}
