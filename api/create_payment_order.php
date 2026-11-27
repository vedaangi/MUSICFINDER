<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../check_subscription.php';
require_once __DIR__ . '/../config/payment.php';

$user_id = requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verifyCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Security token invalid or expired.']);
    exit;
}

// Check if user already has an active subscription
$existingSub = getUserSubscription($conn, $user_id);
if ($existingSub !== null && $existingSub['is_active']) {
    echo json_encode([
        'status' => 'already_active',
        'message' => 'You already have an active subscription valid until ' . $existingSub['end_date'],
        'end_date' => $existingSub['end_date']
    ]);
    exit;
}

$receipt = 'rcpt_u' . $user_id . '_' . time();
$orderResult = createGatewayOrder($user_id, $receipt, [
    'email' => $_SESSION['email'] ?? '',
    'username' => $_SESSION['username'] ?? ''
]);

if (!$orderResult['success']) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize payment gateway order: ' . ($orderResult['error'] ?? 'Unknown error')
    ]);
    exit;
}

$orderId = $orderResult['order_id'];
$amountInr = 99.00;

try {
    $stmt = $conn->prepare(
        "INSERT INTO payments (user_id, order_id, amount, currency, status, payment_method)
         VALUES (?, ?, ?, 'INR', 'created', 'razorpay')"
    );
    if ($stmt) {
        $stmt->bind_param('isd', $user_id, $orderId, $amountInr);
        $stmt->execute();
        $stmt->close();
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error recording payment attempt.'
    ]);
    exit;
}

// Fetch user details for prefill
$userQuery = $conn->query("SELECT username, email FROM users WHERE id = $user_id LIMIT 1");
$userData = $userQuery ? $userQuery->fetch_assoc() : null;

echo json_encode([
    'status' => 'success',
    'order_id' => $orderId,
    'amount' => $orderResult['amount'],
    'currency' => $orderResult['currency'],
    'key_id' => $orderResult['key_id'],
    'mode' => $orderResult['mode'],
    'is_live_configured' => isGatewayLiveConfigured(),
    'plan_name' => 'VARP Premium',
    'prefill' => [
        'name' => $userData['username'] ?? 'VARP Member',
        'email' => $userData['email'] ?? 'user@example.com'
    ]
]);
