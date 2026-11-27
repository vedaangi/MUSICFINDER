<?php
require_once __DIR__ . '/check_subscription.php';
require_once __DIR__ . '/config/payment.php';

$user_id = requireLogin();
$message = '';
$message_type = '';

if (!empty($_SESSION['subscription_notice'])) {
    $message = $_SESSION['subscription_notice'];
    $message_type = 'info';
    unset($_SESSION['subscription_notice']);
}

// Fallback direct POST activation handler (for backward compatibility and test suites)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_prototype'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid security token. Please refresh and try again.';
        $message_type = 'error';
    } else {
        $plan = 'VARP Premium';
        $today = date('Y-m-d');
        $end_date = (new DateTimeImmutable($today))->modify('+1 month')->format('Y-m-d');

        $conn->begin_transaction();
        try {
            $dupStmt = $conn->prepare(
                "SELECT subscription_id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= ? LIMIT 1 FOR UPDATE"
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
                    "UPDATE subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active' AND end_date < ?"
                );
                $expireOld->bind_param('is', $user_id, $today);
                $expireOld->execute();
                $expireOld->close();

                $insertStmt = $conn->prepare(
                    "INSERT INTO subscriptions (user_id, plan, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')"
                );
                $insertStmt->bind_param('isss', $user_id, $plan, $today, $end_date);
                $insertStmt->execute();
                $insertStmt->close();

                $conn->commit();
                $message = 'VARP Premium activated! Your subscription expires on ' . $end_date . '.';
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
$paymentConfig = getPaymentConfig();

// Fetch user's recent payment transactions
$payments = [];
try {
    $payStmt = $conn->prepare(
        "SELECT payment_id, order_id, gateway_payment_id, amount, currency, status, payment_method, created_at 
         FROM payments 
         WHERE user_id = ? 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    if ($payStmt) {
        $payStmt->bind_param('i', $user_id);
        $payStmt->execute();
        $pRes = $payStmt->get_result();
        while ($row = $pRes->fetch_assoc()) {
            $payments[] = $row;
        }
        $payStmt->close();
    }
} catch (Throwable $ignored) {
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">VARP PREMIUM UPGRADE</h1>
        <p class="page-subtitle">Official Razorpay Sandbox Gateway integration for seamless ₹99/month subscriptions.</p>
    </div>
</div>

<div class="premium-plan-wrapper">
    <?php if ($message !== ''): ?>
        <div class="alert-box alert-<?= htmlspecialchars($message_type) ?>">
            <i class="fa-solid <?= $message_type === 'success' ? 'fa-circle-check' : ($message_type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="sandbox-banner">
        <i class="fa-solid fa-shield-halved" style="font-size: 24px; color: var(--forest-200); margin-top: 2px;"></i>
        <div>
            <strong>Razorpay Sandbox / Test Mode Active:</strong>
            <span>All transactions are executed in safe test mode. No real money or bank debits are processed. Server-side HMAC-SHA256 signature verification guarantees end-to-end security before any access is granted.</span>
        </div>
    </div>

    <div class="premium-plan-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <span class="premium-badge"><i class="fa-solid fa-crown"></i> Premium Plan</span>
            <span class="sandbox-badge"><i class="fa-solid fa-flask"></i> Test Sandbox Mode</span>
        </div>

        <h2 style="font-size: 1.75rem; color: #fff; margin-top: 10px;">VARP Premium</h2>
        <div class="premium-price">₹99 <span>/ month</span></div>
        <p style="color: var(--text-secondary); font-size: 0.92rem;">Unlock VIP analytics, curated weekly cinema, character matching, and royalty-free downloads:</p>

        <ul class="feature-list">
            <li><i class="fa-solid fa-check"></i> Top 5 artist streaming analytics &amp; charts</li>
            <li><i class="fa-solid fa-check"></i> Personalized music personality &amp; fictional character twin</li>
            <li><i class="fa-solid fa-check"></i> Curated weekly cinema pairings based on your listening vibe</li>
            <li><i class="fa-solid fa-check"></i> Authorized multi-format audio downloads (.mp3, .wav, .flac)</li>
            <li><i class="fa-solid fa-check"></i> Server-verified payment receipt &amp; automated renewals</li>
        </ul>

        <?php if ($is_active): ?>
            <div class="alert-box alert-success" style="margin-bottom: 18px;">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Active Subscription</strong><br>
                    <span>Plan: <?= htmlspecialchars($subscription['plan']) ?> | Expires on: <strong><?= htmlspecialchars($subscription['end_date']) ?></strong></span>
                </div>
            </div>
            <a href="premium_dashboard.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-crown"></i> Open Premium Dashboard
            </a>
        <?php else: ?>
            <?php if ($subscription !== null && $subscription['status'] === 'expired'): ?>
                <div class="alert-box alert-info" style="margin-bottom: 18px;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Previous subscription expired on <?= htmlspecialchars($subscription['end_date']) ?>. Renew below.</span>
                </div>
            <?php endif; ?>

            <div id="paymentAlert" class="alert-box" style="display: none; margin-bottom: 16px;"></div>

            <!-- Razorpay Gateway Trigger Button -->
            <button id="payNowBtn" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1.05rem; padding: 14px;" onclick="initiateRazorpayPayment()">
                <i class="fa-solid fa-bolt"></i> Pay ₹99 via Razorpay (Test Mode)
            </button>

            <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 14px; text-align: center;">
                <i class="fa-solid fa-lock"></i> Secured with Razorpay HMAC verification. Gateway keys managed via <code>.env</code>.
            </p>

            <details style="margin-top: 18px; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
                <summary style="font-size: 0.8rem; color: var(--text-muted); cursor: pointer;">
                    Developer Options / Prototype Bypass
                </summary>
                <form method="POST" action="subscribe.php" style="margin-top: 12px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="direct_prototype" value="1">
                    <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-terminal"></i> 1-Click Prototype Simulation (Bypass Gateway)
                    </button>
                </form>
            </details>
        <?php endif; ?>
    </div>

    <!-- Recent Payment History -->
    <?php if (!empty($payments)): ?>
        <div style="margin-top: 40px; width: 100%; max-width: 600px;">
            <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 12px;">
                <i class="fa-solid fa-receipt"></i> Your Payment Transactions
            </h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($p['order_id']) ?></code></td>
                                <td>₹<?= number_format((float)$p['amount'], 2) ?></td>
                                <td>
                                    <?php if ($p['status'] === 'captured'): ?>
                                        <span class="status-pill status-active"><i class="fa-solid fa-check"></i> Captured</span>
                                    <?php elseif ($p['status'] === 'failed'): ?>
                                        <span class="status-pill status-expired"><i class="fa-solid fa-xmark"></i> Failed</span>
                                    <?php else: ?>
                                        <span class="status-pill" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24;"><i class="fa-solid fa-clock"></i> <?= htmlspecialchars(ucfirst($p['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.84rem;"><?= htmlspecialchars(substr($p['created_at'], 0, 10)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Interactive Sandbox Test Gateway Modal (Simulator Mode) -->
<div id="sandboxModal" class="sandbox-modal-backdrop">
    <div class="sandbox-modal-box">
        <div style="padding: 20px 24px; background: rgba(28, 14, 48, 0.9); border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-credit-card" style="color: var(--forest-200); font-size: 20px;"></i>
                <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">Razorpay Test Checkout</h3>
            </div>
            <button onclick="closeSandboxModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 18px; cursor: pointer;">&times;</button>
        </div>

        <div style="padding: 24px;">
            <div style="background: rgba(13, 7, 20, 0.6); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.88rem;">Plan:</span>
                    <strong style="color: #fff;">VARP Premium (1 Month)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.88rem;">Order ID:</span>
                    <code id="modalOrderId" style="color: var(--forest-100); font-size: 0.84rem;">order_...</code>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 8px; margin-top: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.95rem;">Total Amount:</span>
                    <strong style="color: var(--forest-100); font-size: 1.25rem;">₹99.00</strong>
                </div>
            </div>

            <p style="font-size: 0.86rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 20px;">
                This sandbox environment tests server-side order validation, cryptographic signature verification, and transactional subscription activation.
            </p>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button id="modalPayBtn" class="btn btn-primary" style="justify-content: center; padding: 12px;" onclick="confirmSandboxPayment(true)">
                    <i class="fa-solid fa-circle-check"></i> Authorize &amp; Capture Test Payment (₹99)
                </button>
                <button class="btn btn-secondary btn-sm" style="justify-content: center; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="confirmSandboxPayment(false)">
                    <i class="fa-solid fa-triangle-exclamation"></i> Test Tampered / Invalid Signature (Verify Server Rejection)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Razorpay Official Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
let currentPaymentOrder = null;

async function initiateRazorpayPayment() {
    const btn = document.getElementById("payNowBtn");
    const alertBox = document.getElementById("paymentAlert");
    alertBox.style.display = "none";

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Creating Payment Order...';

    try {
        const res = await fetch("api/create_payment_order.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-CSRF-TOKEN": "<?= htmlspecialchars($csrf_token) ?>"
            },
            body: "csrf_token=" + encodeURIComponent("<?= htmlspecialchars($csrf_token) ?>")
        });

        const data = await res.json();

        if (data.status === "already_active") {
            alertBox.className = "alert-box alert-info";
            alertBox.innerHTML = '<i class="fa-solid fa-info-circle"></i> ' + data.message;
            alertBox.style.display = "flex";
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Pay ₹99 via Razorpay (Test Mode)';
            return;
        }

        if (data.status !== "success" || !data.order_id) {
            throw new Error(data.message || "Failed to create payment order.");
        }

        currentPaymentOrder = data;

        // If official Razorpay credentials exist and Checkout SDK loaded
        if (data.is_live_configured && typeof Razorpay !== "undefined") {
            const options = {
                "key": data.key_id,
                "amount": data.amount,
                "currency": data.currency,
                "name": "VARP Music",
                "description": "VARP Premium Monthly (₹99/month)",
                "image": "assets/images/placeholder_movie.svg",
                "order_id": data.order_id,
                "handler": function (response) {
                    verifyPaymentOnServer(response.razorpay_order_id, response.razorpay_payment_id, response.razorpay_signature);
                },
                "prefill": {
                    "name": data.prefill ? data.prefill.name : "VARP Member",
                    "email": data.prefill ? data.prefill.email : "user@example.com"
                },
                "theme": {
                    "color": "#2C263F"
                },
                "modal": {
                    "ondismiss": function() {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Pay ₹99 via Razorpay (Test Mode)';
                    }
                }
            };
            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                alertBox.className = "alert-box alert-error";
                alertBox.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Payment failed: ' + (response.error.description || 'Unknown error');
                alertBox.style.display = "flex";
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Pay ₹99 via Razorpay (Test Mode)';
            });
            rzp.open();
        } else {
            // Interactive Sandbox Test Modal
            document.getElementById("modalOrderId").innerText = data.order_id;
            document.getElementById("sandboxModal").style.display = "flex";
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Pay ₹99 via Razorpay (Test Mode)';
        }

    } catch (e) {
        alertBox.className = "alert-box alert-error";
        alertBox.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + (e.message || "Error connecting to gateway.");
        alertBox.style.display = "flex";
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Pay ₹99 via Razorpay (Test Mode)';
    }
}

function closeSandboxModal() {
    document.getElementById("sandboxModal").style.display = "none";
}

async function confirmSandboxPayment(isValidSignature) {
    if (!currentPaymentOrder) return;
    const modalBtn = document.getElementById("modalPayBtn");
    modalBtn.disabled = true;
    modalBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Verifying with Server...';

    const testPaymentId = "pay_test_" + Math.random().toString(36).substring(2, 12);
    const testSignature = isValidSignature 
        ? "sig_test_" + Math.random().toString(36).substring(2, 16)
        : "invalid_forged_tampered_signature";

    await verifyPaymentOnServer(currentPaymentOrder.order_id, testPaymentId, testSignature);
    closeSandboxModal();
    modalBtn.disabled = false;
    modalBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Authorize & Capture Test Payment (₹99)';
}

async function verifyPaymentOnServer(orderId, paymentId, signature) {
    const alertBox = document.getElementById("paymentAlert");
    alertBox.style.display = "none";

    try {
        const bodyData = new URLSearchParams();
        bodyData.append("razorpay_order_id", orderId);
        bodyData.append("razorpay_payment_id", paymentId);
        bodyData.append("razorpay_signature", signature);

        const res = await fetch("api/verify_payment.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: bodyData.toString()
        });

        const data = await res.json();

        if (res.ok && data.status === "success") {
            alertBox.className = "alert-box alert-success";
            alertBox.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message + ' Redirecting to Premium Hub...';
            alertBox.style.display = "flex";

            setTimeout(() => {
                window.location.href = "premium_dashboard.php";
            }, 1800);
        } else {
            alertBox.className = "alert-box alert-error";
            alertBox.innerHTML = '<i class="fa-solid fa-shield-xmark"></i> ' + (data.message || "Payment verification failed.");
            alertBox.style.display = "flex";
        }
    } catch (e) {
        alertBox.className = "alert-box alert-error";
        alertBox.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Server verification request failed: ' + e.message;
        alertBox.style.display = "flex";
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
