<?php
// Razorpay Webhook Endpoint
// Processes asynchronous payment notifications (e.g. payment.captured, order.paid)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payment.php';

header('Content-Type: application/json');

$rawPayload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($rawPayload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty webhook payload.']);
    exit;
}

// Verify webhook signature
if (!verifyWebhookSignature($rawPayload, $signatureHeader)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature.']);
    exit;
}

$event = json_decode($rawPayload, true);
if (!$event || !isset($event['event'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON structure.']);
    exit;
}

$eventType = $event['event'];

if ($eventType === 'payment.captured' || $eventType === 'order.paid') {
    $payloadData = $event['payload']['payment']['entity'] ?? ($event['payload']['order']['entity'] ?? null);
    if ($payloadData) {
        $orderId = $payloadData['order_id'] ?? '';
        $paymentId = $payloadData['id'] ?? '';
        
        if (!empty($orderId)) {
            // Find user from payments record
            $stmt = $conn->prepare("SELECT payment_id, user_id, status FROM payments WHERE order_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $orderId);
                $stmt->execute();
                $rec = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($rec && $rec['status'] !== 'captured') {
                    $userId = (int)$rec['user_id'];
                    $today = date('Y-m-d');
                    $nextMonth = (new DateTimeImmutable($today))->modify('+1 month')->format('Y-m-d');

                    // Activate subscription idempotently
                    $conn->query("UPDATE payments SET status = 'captured', gateway_payment_id = '$paymentId' WHERE order_id = '$orderId'");
                    $conn->query("INSERT INTO subscriptions (user_id, plan, start_date, end_date, status) VALUES ($userId, 'VARP Premium', '$today', '$nextMonth', 'active')");
                }
            }
        }
    }
}

http_response_code(200);
echo json_encode(['status' => 'success', 'event' => $eventType]);
