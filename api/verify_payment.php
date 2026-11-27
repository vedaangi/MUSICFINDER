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

// Read inputs
$order_id = trim($_POST['razorpay_order_id'] ?? '');
$payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

if (empty($order_id) || empty($payment_id) || empty($signature)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required payment verification parameters (order_id, payment_id, or signature).'
    ]);
    exit;
}

// 1. Verify that order exists for this user in DB
$orderStmt = $conn->prepare(
    "SELECT payment_id, user_id, amount, currency, status 
     FROM payments 
     WHERE order_id = ? AND user_id = ? 
     LIMIT 1"
);
if (!$orderStmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit;
}

$orderStmt->bind_param('si', $order_id, $user_id);
$orderStmt->execute();
$paymentRecord = $orderStmt->get_result()->fetch_assoc();
$orderStmt->close();

if (!$paymentRecord) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Order not found or does not belong to the authenticated user.'
    ]);
    exit;
}

if ($paymentRecord['status'] === 'captured') {
    echo json_encode([
        'status' => 'success',
        'message' => 'This payment has already been verified and applied to your account.',
        'already_applied' => true
    ]);
    exit;
}

// 2. CRITICAL SECURITY CHECK: Cryptographic server-side signature verification
$isSignatureValid = verifyPaymentSignature($order_id, $payment_id, $signature);

if (!$isSignatureValid) {
    // Record failure in DB for security audit
    $failStmt = $conn->prepare(
        "UPDATE payments SET status = 'failed', gateway_payment_id = ?, gateway_signature = ? WHERE order_id = ?"
    );
    if ($failStmt) {
        $failStmt->bind_param('sss', $payment_id, $signature, $order_id);
        $failStmt->execute();
        $failStmt->close();
    }

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Security Error: Server payment signature verification failed. Premium access was not granted.'
    ]);
    exit;
}

// 3. In live/sandbox API mode, double verify status with Razorpay API
$gatewayDetails = fetchPaymentDetails($payment_id);
if ($gatewayDetails !== null && isset($gatewayDetails['status']) && !in_array($gatewayDetails['status'], ['captured', 'authorized'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Payment status is ' . htmlspecialchars($gatewayDetails['status']) . '; not captured.'
    ]);
    exit;
}

// 4. Activate or extend subscription in database transaction
$conn->begin_transaction();
try {
    $plan = 'VARP Premium';
    $today = date('Y-m-d');
    $nextMonth = (new DateTimeImmutable($today))->modify('+1 month')->format('Y-m-d');

    // Expire any outdated previous subscriptions
    $expireOld = $conn->prepare(
        "UPDATE subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active' AND end_date < ?"
    );
    $expireOld->bind_param('is', $user_id, $today);
    $expireOld->execute();
    $expireOld->close();

    // Check if active subscription exists to extend it or create new
    $activeCheck = $conn->prepare(
        "SELECT subscription_id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active' LIMIT 1 FOR UPDATE"
    );
    $activeCheck->bind_param('i', $user_id);
    $activeCheck->execute();
    $activeRow = $activeCheck->get_result()->fetch_assoc();
    $activeCheck->close();

    $newSubscriptionId = 0;
    if ($activeRow) {
        // Extend existing subscription by 1 month from current end_date
        $curEnd = $activeRow['end_date'];
        $extendedEnd = (new DateTimeImmutable($curEnd))->modify('+1 month')->format('Y-m-d');
        $upSub = $conn->prepare("UPDATE subscriptions SET end_date = ?, plan = ? WHERE subscription_id = ?");
        $subId = (int)$activeRow['subscription_id'];
        $upSub->bind_param('ssi', $extendedEnd, $plan, $subId);
        $upSub->execute();
        $upSub->close();
        $newSubscriptionId = $subId;
        $expiryDate = $extendedEnd;
    } else {
        // Insert new active subscription
        $newSub = $conn->prepare(
            "INSERT INTO subscriptions (user_id, plan, start_date, end_date, status)
             VALUES (?, ?, ?, ?, 'active')"
        );
        $newSub->bind_param('isss', $user_id, $plan, $today, $nextMonth);
        $newSub->execute();
        $newSubscriptionId = $conn->insert_id;
        $newSub->close();
        $expiryDate = $nextMonth;
    }

    // 5. Update payment record to captured
    $upPay = $conn->prepare(
        "UPDATE payments 
         SET status = 'captured', gateway_payment_id = ?, gateway_signature = ?, subscription_id = ?
         WHERE order_id = ?"
    );
    $upPay->bind_param('ssis', $payment_id, $signature, $newSubscriptionId, $order_id);
    $upPay->execute();
    $upPay->close();

    // 6. Log user activity
    $actStmt = $conn->prepare(
        "INSERT INTO user_activity (user_id, activity_type, description)
         VALUES (?, 'Payment', ?)"
    );
    if ($actStmt) {
        $desc = "Paid ₹99.00 via Razorpay (Order: $order_id, Payment: $payment_id). Active until $expiryDate.";
        $actStmt->bind_param('is', $user_id, $desc);
        $actStmt->execute();
        $actStmt->close();
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment verified! Your VARP Premium subscription is active until ' . $expiryDate . '.',
        'subscription_id' => $newSubscriptionId,
        'end_date' => $expiryDate
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error while activating subscription: ' . $e->getMessage()
    ]);
}
