<?php
// VARP Payment Gateway Configuration & SDK Utilities
// Official Sandbox / Test Mode Integration for Razorpay

if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void {
        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip quotes if present
                if (preg_match('/^"(.*)"$/', $value, $m) || preg_match("/^'(.*)'$/", $value, $m)) {
                    $value = $m[1];
                }

                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// Load .env if present
loadEnvFile(__DIR__ . '/../.env');

// Gateway Settings
function getPaymentConfig(): array {
    $mode = getenv('PAYMENT_GATEWAY_MODE') ?: ($_ENV['PAYMENT_GATEWAY_MODE'] ?? 'sandbox');
    $keyId = getenv('RAZORPAY_KEY_ID') ?: ($_ENV['RAZORPAY_KEY_ID'] ?? '');
    $keySecret = getenv('RAZORPAY_KEY_SECRET') ?: ($_ENV['RAZORPAY_KEY_SECRET'] ?? '');
    $webhookSecret = getenv('RAZORPAY_WEBHOOK_SECRET') ?: ($_ENV['RAZORPAY_WEBHOOK_SECRET'] ?? '');
    
    $isConfigured = !empty($keyId) && !empty($keySecret) && !str_contains($keyId, 'YourKeyId');

    return [
        'gateway' => 'razorpay',
        'mode' => strtolower($mode),
        'key_id' => $keyId,
        'key_secret' => $keySecret,
        'webhook_secret' => $webhookSecret,
        'is_configured' => $isConfigured,
        'amount_inr' => 99.00,
        'amount_paise' => 9900,
        'currency' => 'INR',
        'plan_name' => 'VARP Premium'
    ];
}

// Check if credentials are fully configured
function isGatewayLiveConfigured(): bool {
    $cfg = getPaymentConfig();
    return $cfg['is_configured'];
}

// Create an Order with Razorpay API (or test sandbox order)
function createGatewayOrder(int $user_id, string $receipt, array $notes = []): array {
    $cfg = getPaymentConfig();
    $amountPaise = $cfg['amount_paise'];
    $currency = $cfg['currency'];

    if ($cfg['is_configured']) {
        // Official Razorpay API call
        $url = 'https://api.razorpay.com/v1/orders';
        $payload = json_encode([
            'amount' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'notes' => array_merge([
                'user_id' => (string)$user_id,
                'plan' => $cfg['plan_name']
            ], $notes)
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $cfg['key_id'] . ':' . $cfg['key_secret']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            $data = json_decode($response, true);
            if ($data && !empty($data['id'])) {
                return [
                    'success' => true,
                    'order_id' => $data['id'],
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'key_id' => $cfg['key_id'],
                    'mode' => 'live_sandbox'
                ];
            }
        }

        // If official gateway API returned error, report detail
        $err = json_decode($response, true);
        $errMsg = $err['error']['description'] ?? 'Razorpay API returned HTTP code ' . $httpCode;
        return [
            'success' => false,
            'error' => $errMsg
        ];
    }

    // Sandbox Test Simulator Mode (when keys not yet inserted into .env)
    $simulatedOrderId = 'order_test_' . bin2hex(random_bytes(8));
    return [
        'success' => true,
        'order_id' => $simulatedOrderId,
        'amount' => $amountPaise,
        'currency' => $currency,
        'key_id' => !empty($cfg['key_id']) ? $cfg['key_id'] : 'rzp_test_simulated_varp',
        'mode' => 'simulator'
    ];
}

// Verify payment signature
function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool {
    $cfg = getPaymentConfig();

    if ($cfg['is_configured']) {
        // Standard Razorpay HMAC-SHA256 signature algorithm
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $cfg['key_secret']);
        return hash_equals($expectedSignature, $signature);
    }

    // In simulator mode, check simulated HMAC or test prefix
    $devSecret = 'varp_dev_secret_for_local_testing_only';
    $expectedSimulated = hash_hmac('sha256', $orderId . '|' . $paymentId, $devSecret);

    if (hash_equals($expectedSimulated, $signature)) {
        return true;
    }

    // Allow test simulator signature format
    if (str_starts_with($signature, 'sig_test_') && str_starts_with($paymentId, 'pay_test_')) {
        return true;
    }

    return false;
}

// Verify Razorpay Webhook signature
function verifyWebhookSignature(string $rawPayload, string $signature): bool {
    $cfg = getPaymentConfig();
    $secret = $cfg['webhook_secret'];

    if (empty($secret)) {
        return false;
    }

    $expected = hash_hmac('sha256', $rawPayload, $secret);
    return hash_equals($expected, $signature);
}

// Fetch payment details directly from Razorpay (for double verification)
function fetchPaymentDetails(string $paymentId): ?array {
    $cfg = getPaymentConfig();
    if (!$cfg['is_configured']) {
        return [
            'id' => $paymentId,
            'status' => 'captured',
            'amount' => $cfg['amount_paise'],
            'currency' => $cfg['currency']
        ];
    }

    $url = 'https://api.razorpay.com/v1/payments/' . urlencode($paymentId);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $cfg['key_id'] . ':' . $cfg['key_secret']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }

    return null;
}
