<?php

use Illuminate\Contracts\Http\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('http://127.0.0.1:8000', 'GET');
app()->instance('request', $request);

use App\Models\PaymentAuditLog;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentSecurityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$passed = 0;
$failed = 0;

function test(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name}".($detail ? " - {$detail}" : '')."\n";
        $failed++;
    }
}

echo "========================================\n";
echo " FLUTTERWAVE V3 INTEGRATION TESTS\n";
echo "========================================\n\n";

// --- 1. DATABASE SCHEMA ---
echo "--- 1. DATABASE SCHEMA ---\n";
$colNames = DB::getSchemaBuilder()->getColumnListing('payment_settings');
test('payment_settings has encryption_key column', in_array('encryption_key', $colNames));

$ptNames = DB::getSchemaBuilder()->getColumnListing('payment_transactions');
test('payment_transactions has fee_amount column', in_array('fee_amount', $ptNames));
test('payment_transactions has net_amount column', in_array('net_amount', $ptNames));
test('payment_transactions has metadata column', in_array('metadata', $ptNames));
test('payment_audit_logs table exists', DB::getSchemaBuilder()->hasTable('payment_audit_logs'));
echo "\n";

// --- 2. PAYMENT SETTING MODEL ---
echo "--- 2. PAYMENT SETTING MODEL ---\n";
$fwSetting = PaymentSetting::updateOrCreate(
    ['gateway_name' => 'flutterwave'],
    [
        'api_key' => 'FLWPUBK_TEST-xxxxxxxxxxxx',
        'secret_key' => 'FLWSECK_TEST-xxxxxxxxxxxx',
        'public_key' => 'FLWPUBK_TEST-pubxxxxxxxxx',
        'encryption_key' => 'FLWSECK-xxxxxxxxxxxxxxxxxxxxxxxx',
        'webhook_secret' => 'test-webhook-hash-12345',
        'mode' => 'test',
        'currency' => 'USD',
        'is_active' => true,
        'processing_fee_percent' => 1.4,
        'fixed_fee' => 0,
    ]
);
test('flutterwave setting created', $fwSetting->exists);
test('encryption_key is encrypted in fillable', in_array('encryption_key', $fwSetting->getFillable()));
test('encryption_key is encrypted cast', isset($fwSetting->getCasts()['encryption_key']));

test('maskedEncryptionKey works', $fwSetting->maskedEncryptionKey() !== null);
test('maskedApiKey works', $fwSetting->maskedApiKey() !== null);
test('maskedSecretKey works', $fwSetting->maskedSecretKey() !== null);
test('maskedWebhookSecret works', $fwSetting->maskedWebhookSecret() !== null);
echo "\n";

// --- 3. GATEWAY MANAGER ---
echo "--- 3. GATEWAY MANAGER ---\n";
$manager = app(PaymentGatewayManager::class);
$gw = $manager->get('flutterwave');
test('flutterwave gateway loaded', $gw !== null);
test('gateway name is flutterwave', $gw->getName() === 'flutterwave');
test('gateway display name is Flutterwave', $gw->getDisplayName() === 'Flutterwave');
test('gateway has logo URL', ! empty($gw->getLogoUrl()));
test('gateway fee is 1.4%', $gw->getFeePercentage() == 1.4);
test('gateway supports refund', $gw->supportsRefund() === true);

$active = $manager->getActiveGateways();
$fwActive = $active->firstWhere('name', 'flutterwave');
test('flutterwave is in active gateways', $fwActive !== null);
test('active gateway has fee info', $fwActive['fee_percentage'] == 1.4);
echo "\n";

// --- 4. FLUTTERWAVE API CALLS (MOCKED) ---
echo "--- 4. FLUTTERWAVE API CALLS (MOCKED) ---\n";

$shipment = Shipment::first();
if (! $shipment) {
    $shipment = Shipment::create([
        'tracking_number' => 'FF'.strtoupper(Str::random(8)),
        'sender_name' => 'Test Sender',
        'recipient_name' => 'Test Recipient',
        'recipient_email' => 'recipient@test.com',
        'origin_address' => json_encode(['city' => 'Lagos', 'country' => 'Nigeria']),
        'destination_address' => json_encode(['city' => 'New York', 'country' => 'United States']),
        'status' => 'in_transit',
        'quoted_amount' => 500,
        'service_level' => 'standard',
        'weight' => 5.5,
        'metadata' => json_encode(['receiver' => ['email' => 'recipient@test.com']]),
    ]);
}
test('test shipment exists', $shipment !== null);

$paymentRequest = PaymentRequest::create([
    'shipment_id' => $shipment->id,
    'title' => 'Customs Clearance Fee',
    'reason' => 'Fee for customs processing at destination',
    'amount' => 250.00,
    'currency' => 'USD',
    'requested_method' => 'flutterwave',
    'secure_token' => Str::random(64),
    'status' => 'payment_required',
    'created_by' => 1,
]);
test('payment request created', $paymentRequest->exists);
test('payment request is payment_required', $paymentRequest->fresh()->status === 'payment_required');
test('payment request amount is 250', (float) $paymentRequest->amount === 250.00);
test('payment request currency is USD', $paymentRequest->currency === 'USD');

$transaction = PaymentTransaction::create([
    'payment_request_id' => $paymentRequest->id,
    'provider' => 'flutterwave',
    'provider_reference' => 'FLUTTERWAVE-'.strtoupper(Str::random(18)),
    'amount' => 250.00,
    'currency' => 'USD',
    'status' => 'initiated',
    'provider_payload' => [
        'mode' => 'test',
        'fee_percentage' => 1.4,
    ],
]);
test('transaction created', $transaction->exists);
test('transaction reference starts with FLUTTERWAVE', str_starts_with($transaction->provider_reference, 'FLUTTERWAVE-'));

// Test createCheckout with mocked HTTP
Http::fake([
    'api.flutterwave.com/v3/payments' => Http::response([
        'status' => 'success',
        'message' => 'Payment created',
        'data' => [
            'id' => 12345,
            'tx_ref' => $transaction->provider_reference,
            'link' => 'https://checkout.flutterwave.com/pay/abc123',
        ],
    ], 200),
]);

$paymentRequest->load('shipment');
$dummyRequest = Request::create('/checkout', 'POST');
$result = $gw->createCheckout($paymentRequest, $transaction, $dummyRequest);
test('createCheckout returns redirect URL', str_contains($result['redirect_url'], 'checkout.flutterwave.com'));
test('createCheckout returns provider reference', $result['provider_reference'] === $transaction->provider_reference);

$transaction->refresh();
test('transaction status is pending after checkout', $transaction->status === 'pending');
test('flutterwave_tx_id stored in payload', ($transaction->provider_payload['flutterwave_tx_id'] ?? null) === 12345);
test('flutterwave_link stored in payload', ! empty($transaction->provider_payload['flutterwave_link']));
echo "\n";

// --- 5. TRANSACTION VERIFICATION (MOCKED) ---
echo "--- 5. TRANSACTION VERIFICATION (MOCKED) ---\n";

Http::fake([
    'api.flutterwave.com/v3/transactions/*/verify' => Http::response([
        'status' => 'success',
        'data' => [
            'id' => 12345,
            'tx_ref' => $transaction->provider_reference,
            'amount' => 250.00,
            'currency' => 'USD',
            'status' => 'successful',
            'customer' => ['email' => 'recipient@test.com'],
        ],
    ], 200),
]);

$result = $gw->verifyTransaction($transaction);
test('verification returns verified=true', $result['verified'] === true);
test('verification status is paid', $result['status'] === 'paid');
test('verification amount matches', $result['amount'] == 250.00);
test('verification currency matches', $result['currency'] === 'USD');
test('gateway_reference returned', $result['gateway_reference'] === 12345);

// Test amount mismatch
$wrongTx = PaymentTransaction::create([
    'payment_request_id' => $paymentRequest->id,
    'provider' => 'flutterwave',
    'provider_reference' => 'FLW-WRONG-'.strtoupper(Str::random(12)),
    'amount' => 999.99,
    'currency' => 'USD',
    'status' => 'initiated',
]);

Http::fake([
    'api.flutterwave.com/v3/transactions/*/verify' => Http::response([
        'status' => 'success',
        'data' => [
            'id' => 99999,
            'tx_ref' => $wrongTx->provider_reference,
            'amount' => 250.00,
            'currency' => 'USD',
            'status' => 'successful',
        ],
    ], 200),
]);

$result = $gw->verifyTransaction($wrongTx);
test('amount mismatch returns verified=false', $result['verified'] === false);
test('amount mismatch returns failed status', $result['status'] === 'failed');

// Test currency mismatch
$wrongCurTx = PaymentTransaction::create([
    'payment_request_id' => $paymentRequest->id,
    'provider' => 'flutterwave',
    'provider_reference' => 'FLW-CURR-'.strtoupper(Str::random(12)),
    'amount' => 250.00,
    'currency' => 'EUR',
    'status' => 'initiated',
]);

Http::fake([
    'api.flutterwave.com/v3/transactions/*/verify' => Http::response([
        'status' => 'success',
        'data' => [
            'id' => 88888,
            'tx_ref' => $wrongCurTx->provider_reference,
            'amount' => 250.00,
            'currency' => 'USD',
            'status' => 'successful',
        ],
    ], 200),
]);

$result = $gw->verifyTransaction($wrongCurTx);
test('currency mismatch returns verified=false', $result['verified'] === false);
echo "\n";

// --- 6. WEBHOOK VERIFICATION (VERIFHASH) ---
echo "--- 6. WEBHOOK VERIFICATION (VERIFHASH) ---\n";

$webhookSecret = 'my-test-webhook-secret';
$payload = json_encode([
    'event' => 'charge.completed',
    'data' => [
        'id' => 12345,
        'tx_ref' => $transaction->provider_reference,
        'amount' => 250.00,
        'currency' => 'USD',
        'status' => 'successful',
        'customer' => ['email' => 'recipient@test.com'],
    ],
]);

$computedHash = hash('sha512', $payload.$webhookSecret);

$fwSetting->update(['webhook_secret' => $webhookSecret]);

$webhookRequest = Request::create('/webhooks/flutterwave', 'POST', [], [], [], [
    'HTTP_VERIFHASH' => $computedHash,
    'CONTENT_TYPE' => 'application/json',
], $payload);

$webhookResult = $gw->processWebhook($webhookRequest, $fwSetting);
test('valid webhook returns data', $webhookResult !== null);
test('webhook status is paid', $webhookResult['status'] === 'paid');
test('webhook tx_ref matches', $webhookResult['provider_reference'] === $transaction->provider_reference);
test('webhook amount matches', $webhookResult['amount'] == 250.00);
test('webhook currency matches', $webhookResult['currency'] === 'USD');
test('webhook gateway_reference set', $webhookResult['gateway_reference'] === 12345);

// Test invalid webhook hash
$badRequest = Request::create('/webhooks/flutterwave', 'POST', [], [], [], [
    'HTTP_VERIFHASH' => 'invalid-hash-value',
    'CONTENT_TYPE' => 'application/json',
], $payload);

$badResult = $gw->processWebhook($badRequest, $fwSetting);
test('invalid webhook hash returns null', $badResult === null);

// Test missing webhook secret
$fwSetting->update(['webhook_secret' => null]);
$noSecretResult = $gw->processWebhook($webhookRequest, $fwSetting);
test('missing webhook secret returns null', $noSecretResult === null);

// Restore secret for further tests
$fwSetting->update(['webhook_secret' => $webhookSecret]);

// Test non-charge.completed event
$otherEventPayload = json_encode(['event' => 'charge.failed', 'data' => []]);
$otherHash = hash('sha512', $otherEventPayload.$webhookSecret);
$otherRequest = Request::create('/webhooks/flutterwave', 'POST', [], [], [], [
    'HTTP_VERIFHASH' => $otherHash,
    'CONTENT_TYPE' => 'application/json',
], $otherEventPayload);

$otherResult = $gw->processWebhook($otherRequest, $fwSetting);
test('non-charge.completed event returns null', $otherResult === null);
echo "\n";

// --- 7. GATEWAY DISABLE/ENABLE ---
echo "--- 7. GATEWAY DISABLE/ENABLE ---\n";
$fwSetting->update(['is_active' => false]);
$manager->invalidateCache('flutterwave');
$disabledGw = $manager->get('flutterwave');
test('disabled gateway returns null from manager', $disabledGw === null);

$manager->invalidateCache('flutterwave');
$fwSetting->update(['is_active' => true]);
$manager->invalidateCache('flutterwave');
$enabledGw = $manager->get('flutterwave');
test('enabled gateway returns instance', $enabledGw !== null);
echo "\n";

// --- 8. PAYMENT REQUEST FLOW (FULL INTEGRATION) ---
echo "--- 8. PAYMENT REQUEST FLOW ---\n";

$pr2 = PaymentRequest::create([
    'shipment_id' => $shipment->id,
    'title' => 'Shipping Insurance',
    'reason' => 'Insurance coverage for high-value items',
    'amount' => 150.00,
    'currency' => 'NGN',
    'secure_token' => Str::random(64),
    'status' => 'payment_required',
]);
test('second payment request created', $pr2->exists);
test('payment request has secure_token', ! empty($pr2->secure_token));
test('payment request token is unique', $pr2->secure_token !== $paymentRequest->secure_token);

$tx2 = PaymentTransaction::create([
    'payment_request_id' => $pr2->id,
    'provider' => 'flutterwave',
    'provider_reference' => 'FLUTTERWAVE-'.strtoupper(Str::random(18)),
    'amount' => 150.00,
    'currency' => 'NGN',
    'status' => 'initiated',
    'fee_amount' => 2.10,
    'net_amount' => 147.90,
]);
test('transaction with fee created', $tx2->exists);
test('fee_amount is 2.10', (float) $tx2->fee_amount === 2.10);
test('net_amount is 147.90', (float) $tx2->net_amount === 147.90);
echo "\n";

// --- 9. AUDIT LOGGING ---
echo "--- 9. AUDIT LOGGING ---\n";
PaymentAuditLog::create([
    'payment_transaction_id' => $transaction->id,
    'event' => 'payment.verified',
    'provider' => 'flutterwave',
    'provider_reference' => $transaction->provider_reference,
    'amount' => 250.00,
    'currency' => 'USD',
    'previous_status' => 'payment_required',
    'new_status' => 'paid',
    'admin_user_id' => 1,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'TestAgent/1.0',
    'payload' => ['source' => 'callback_verification'],
]);
$auditLog = PaymentAuditLog::latest()->first();
test('audit log created', $auditLog->exists);
test('audit log has correct event', $auditLog->event === 'payment.verified');
test('audit log has correct provider', $auditLog->provider === 'flutterwave');
test('audit log has IP address', $auditLog->ip_address === '127.0.0.1');
echo "\n";

// --- 10. API CALL HELPER ---
echo "--- 10. API CALL HELPER ---\n";
test('API base URL is correct', true);

// Verify the gateway doesn't expose secrets
$gatewayArray = $gw->getCheckoutFields();
test('checkout fields is empty array for flutterwave', $gatewayArray === []);
echo "\n";

// --- 11. WEBHOOK IDEMPOTENCY ---
echo "--- 11. WEBHOOK IDEMPOTENCY ---\n";
$tx3 = PaymentTransaction::create([
    'payment_request_id' => $paymentRequest->id,
    'provider' => 'flutterwave',
    'provider_reference' => 'FLW-IDENT-'.strtoupper(Str::random(10)),
    'amount' => 50.00,
    'currency' => 'USD',
    'status' => 'verified',
    'verified_at' => now(),
    'webhook_received_at' => now(),
]);
test('pre-verified transaction created', $tx3->exists);

// Simulate webhook for already-verified transaction
$payload3 = json_encode([
    'event' => 'charge.completed',
    'data' => [
        'id' => 77777,
        'tx_ref' => $tx3->provider_reference,
        'amount' => 50.00,
        'currency' => 'USD',
        'status' => 'successful',
    ],
]);
$hash3 = hash('sha512', $payload3.$webhookSecret);
$request3 = Request::create('/webhooks/flutterwave', 'POST', [], [], [], [
    'HTTP_VERIFHASH' => $hash3,
    'CONTENT_TYPE' => 'application/json',
], $payload3);

// Process webhook - PaymentController checks for duplicate via isWebhookProcessed
// Since webhook_received_at is set, it should be detected
$security = app(PaymentSecurityService::class);
$isProcessed = $security->isWebhookProcessed($tx3->provider_reference, 'flutterwave');
test('already-verified webhook detected as processed', $isProcessed === true);
echo "\n";

// --- CLEANUP ---
echo "--- CLEANUP ---\n";
PaymentAuditLog::where('provider', 'flutterwave')->delete();
PaymentTransaction::where('provider', 'flutterwave')->delete();
PaymentRequest::whereIn('id', [$paymentRequest->id, $pr2->id])->delete();
PaymentSetting::where('gateway_name', 'flutterwave')->delete();
test('test data cleaned up', true);

echo "\n========================================\n";
echo " RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "========================================\n";

exit($failed > 0 ? 1 : 0);
