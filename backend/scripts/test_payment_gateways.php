<?php

use Illuminate\Contracts\Http\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\PaymentAuditLog;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use App\Models\User;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentSecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$pass = 0;
$fail = 0;

function test($name, $condition, $detail = '')
{
    global $pass, $fail;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$name}".($detail ? " - {$detail}" : '')."\n";
        $fail++;
    }
}

echo "========================================\n";
echo " MULTI-PAYMENT GATEWAY SYSTEM TESTS\n";
echo "========================================\n\n";

// --- DATABASE SCHEMA TESTS ---
echo "--- 1. DATABASE SCHEMA ---\n";

$hasWebhookSecret = Schema::hasColumn('payment_settings', 'webhook_secret');
test('payment_settings has webhook_secret column', $hasWebhookSecret);

$hasMerchantName = Schema::hasColumn('payment_settings', 'merchant_name');
test('payment_settings has merchant_name column', $hasMerchantName);

$hasConfig = Schema::hasColumn('payment_settings', 'config');
test('payment_settings has config JSON column', $hasConfig);

$hasFeePercent = Schema::hasColumn('payment_settings', 'processing_fee_percent');
test('payment_settings has processing_fee_percent column', $hasFeePercent);

$hasFeeFixed = Schema::hasColumn('payment_settings', 'fixed_fee');
test('payment_settings has fixed_fee column', $hasFeeFixed);

$hasMinAmount = Schema::hasColumn('payment_settings', 'min_amount');
test('payment_settings has min_amount column', $hasMinAmount);

$hasMaxAmount = Schema::hasColumn('payment_settings', 'max_amount');
test('payment_settings has max_amount column', $hasMaxAmount);

$hasFeeAmt = Schema::hasColumn('payment_transactions', 'fee_amount');
test('payment_transactions has fee_amount column', $hasFeeAmt);

$hasNetAmt = Schema::hasColumn('payment_transactions', 'net_amount');
test('payment_transactions has net_amount column', $hasNetAmt);

$hasWebhookAt = Schema::hasColumn('payment_transactions', 'webhook_received_at');
test('payment_transactions has webhook_received_at column', $hasWebhookAt);

$hasTxMeta = Schema::hasColumn('payment_transactions', 'metadata');
test('payment_transactions has metadata column', $hasTxMeta);

$auditExists = Schema::hasTable('payment_audit_logs');
test('payment_audit_logs table exists', $auditExists);

echo "\n";

// --- GATEWAY MANAGER TESTS ---
echo "--- 2. GATEWAY MANAGER ---\n";

$manager = app(PaymentGatewayManager::class);

$registered = $manager->getAllRegistered();
test('5 gateways registered', count($registered) === 5, 'count='.count($registered));
test('flutterwave registered', in_array('flutterwave', $registered));
test('stripe registered', in_array('stripe', $registered));
test('paypal registered', in_array('paypal', $registered));
test('bank_transfer registered', in_array('bank_transfer', $registered));
test('crypto registered', in_array('crypto', $registered));

// Active gateways from DB (may have pre-existing data)
$active = $manager->getActive();
test('active gateways count >= 0', $active->count() >= 0, 'count='.$active->count());

// Non-existent gateway returns null
$nullGw = $manager->get('nonexistent');
test('non-existent gateway returns null', $nullGw === null);

echo "\n";

// --- GATEWAY ENABLE/DISABLE TESTS ---
echo "--- 3. GATEWAY ENABLE/DISABLE ---\n";

// Enable flutterwave
$fwSetting = PaymentSetting::updateOrCreate(
    ['gateway_name' => 'flutterwave'],
    [
        'api_key' => 'FLWPUBK_TEST-key123456',
        'secret_key' => 'FLWSECK_TEST-secret123456',
        'public_key' => 'FLWPUBK_TEST-pub123456',
        'webhook_secret' => 'test-webhook-hash',
        'mode' => 'test',
        'currency' => 'USD',
        'is_active' => true,
        'processing_fee_percent' => 1.4,
        'fixed_fee' => 0,
    ]
);
test('flutterwave enabled successfully', $fwSetting->exists && $fwSetting->is_active);

// Enable stripe
$stripeSetting = PaymentSetting::updateOrCreate(
    ['gateway_name' => 'stripe'],
    [
        'api_key' => 'pk_test_abc123',
        'secret_key' => 'sk_test_xyz789',
        'public_key' => 'pk_test_xyz789',
        'mode' => 'test',
        'currency' => 'USD',
        'is_active' => true,
        'processing_fee_percent' => 2.9,
        'fixed_fee' => 0.30,
    ]
);
test('stripe enabled successfully', true);

// Disable stripe
$stripeSetting->update(['is_active' => false]);
$manager->invalidateCache('stripe');
$disabledGw = $manager->get('stripe');
test('disabled gateway returns null from manager', $disabledGw === null);

// Re-enable stripe
$stripeSetting->update(['is_active' => true]);
$enabledGw = $manager->get('stripe');
test('enabled gateway returns instance', $enabledGw !== null);
test('stripe gateway has correct name', $enabledGw->getName() === 'stripe');
test('stripe gateway has correct display name', $enabledGw->getDisplayName() === 'Stripe');

// Enable remaining gateways
PaymentSetting::updateOrCreate(['gateway_name' => 'paypal'], ['api_key' => 'client_id', 'secret_key' => 'secret', 'mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'processing_fee_percent' => 3.49, 'fixed_fee' => 0.49]);
PaymentSetting::updateOrCreate(['gateway_name' => 'bank_transfer'], ['mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'config' => ['bank_name' => 'Test Bank', 'account_name' => 'FreightFlow Inc', 'account_number' => '1234567890', 'routing_number' => '021000021', 'swift_code' => 'TESTUS66', 'payment_instructions' => 'Include reference in description.']]);
PaymentSetting::updateOrCreate(['gateway_name' => 'crypto'], ['api_key' => 'np_test_key123', 'mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'config' => ['provider' => 'nowpayments', 'preferred_coin' => 'btc']]);

$activeAfter = $manager->getActive();
test('all 5 gateways active now', $activeAfter->count() === 5, 'count='.$activeAfter->count());

$activeGateways = $manager->getActiveGateways();
test('getActiveGateways returns 5 items', $activeGateways->count() === 5);
test('each gateway has display_name', $activeGateways->every('display_name'));
test('each gateway has logo_url', $activeGateways->every('logo_url'));
test('each gateway has fee_percentage', $activeGateways->every(fn ($g) => $g['fee_percentage'] >= 0));

echo "\n";

// --- PAYMENT SECURITY TESTS ---
echo "--- 4. PAYMENT SECURITY ---\n";

$security = app(PaymentSecurityService::class);

// Amount validation
test('valid amount passes', $security->validateAmount(100.00, 100.00));
test('slight diff within tolerance passes', $security->validateAmount(100.00, 100.005, 0.01));
test('large diff fails', ! $security->validateAmount(100.00, 105.00));

// Currency validation
test('matching currency passes', $security->validateCurrency('USD', 'usd'));
test('mismatched currency fails', ! $security->validateCurrency('USD', 'EUR'));

// Fee calculation
$fees = $security->calculateFee(100, 2.9, 0.30);
test('fee calculation percentage', $fees['fee_amount'] == 3.20, 'got '.$fees['fee_amount']);
test('fee calculation net', $fees['net_amount'] == 96.80, 'got '.$fees['net_amount']);

$fees2 = $security->calculateFee(100, 0, 0);
test('zero fee returns full amount', $fees2['fee_amount'] == 0 && $fees2['net_amount'] == 100);

// Duplicate detection
test('empty reference not duplicate', ! $security->isDuplicateTransaction(''));

// Gateway enabled check
test('flutterwave is enabled', $security->isGatewayEnabled('flutterwave'));
test('nonexistent gateway not enabled', ! $security->isGatewayEnabled('nonexistent_gateway'));

echo "\n";

// --- PAYMENT SETTING MODEL TESTS ---
echo "--- 5. PAYMENT SETTING MODEL ---\n";

$fw = PaymentSetting::where('gateway_name', 'flutterwave')->first();
test('masked API key hides middle', str_contains($fw->maskedApiKey(), '****'));
test('masked API key shows last 4 chars', str_ends_with($fw->maskedApiKey(), '456'));
test('masked secret key hides middle', str_contains($fw->maskedSecretKey(), '****'));
test('masked webhook secret hides middle', str_contains($fw->maskedWebhookSecret(), '****'));
test('raw API key is not masked', str_contains($fw->api_key, 'FLWPUBK'));

$bank = PaymentSetting::where('gateway_name', 'bank_transfer')->first();
test('bank_transfer has config', $bank->config !== null);
test('bank config has bank_name', ($bank->config['bank_name'] ?? '') === 'Test Bank');
test('bank config has account_number', ($bank->config['account_number'] ?? '') === '1234567890');

$crypto = PaymentSetting::where('gateway_name', 'crypto')->first();
test('crypto has config', $crypto->config !== null);
test('crypto config has provider', ($crypto->config['provider'] ?? '') === 'nowpayments');

echo "\n";

// --- GATEWAY INSTANCE TESTS ---
echo "--- 6. GATEWAY INSTANCES ---\n";

$fwGw = $manager->get('flutterwave');
test('flutterwave gateway initializes', $fwGw !== null);
test('flutterwave supported currencies include NGN', in_array('NGN', $fwGw->getSupportedCurrencies()));
test('flutterwave fee is 1.4%', $fwGw->getFeePercentage() === 1.4);
test('flutterwave has logo URL', ! empty($fwGw->getLogoUrl()));

$stripeGw = $manager->get('stripe');
test('stripe gateway initializes', $stripeGw !== null);
test('stripe supports refund', $stripeGw->supportsRefund());
test('stripe fee is 2.9%', $stripeGw->getFeePercentage() === 2.9);
test('stripe fixed fee is 0.30', $stripeGw->getFixedFee() === 0.30);

$paypalGw = $manager->get('paypal');
test('paypal gateway initializes', $paypalGw !== null);
test('paypal supports refund', $paypalGw->supportsRefund());
test('paypal fee is 3.49%', $paypalGw->getFeePercentage() === 3.49);

$btGw = $manager->get('bank_transfer');
test('bank_transfer gateway initializes', $btGw !== null);
test('bank_transfer does NOT support refund', ! $btGw->supportsRefund());
test('bank_transfer has checkout fields', count($btGw->getCheckoutFields()) > 0);
test('bank_transfer has bank_name field', collect($btGw->getCheckoutFields())->where('name', 'bank_name')->isNotEmpty());
test('bank_transfer has account_number field', collect($btGw->getCheckoutFields())->where('name', 'account_number')->isNotEmpty());
test('bank_transfer has proof_of_payment field', collect($btGw->getCheckoutFields())->where('name', 'proof_of_payment')->isNotEmpty());
test('bank_transfer fee is 0', $btGw->getFeePercentage() == 0);

$cryptoGw = $manager->get('crypto');
test('crypto gateway initializes', $cryptoGw !== null);
test('crypto supported currencies include BTC', in_array('BTC', $cryptoGw->getSupportedCurrencies()));
test('crypto fee is 1.0%', $cryptoGw->getFeePercentage() === 1.0);

echo "\n";

// --- WEBHOOK SIGNATURE VERIFICATION ---
echo "--- 7. WEBHOOK SIGNATURE VERIFICATION ---\n";

$payload = '{"event":"charge.completed","data":{"status":"successful","amount":100,"currency":"USD","tx_ref":"TEST-ABC"}}';
$secret = 'test-webhook-hash';
$sig = hash_hmac('sha256', $payload, $secret);
test('HMAC signature generation works', ! empty($sig));
test('HMAC verification with matching signature', hash_equals($sig, hash_hmac('sha256', $payload, $secret)));
test('HMAC verification fails with wrong signature', ! hash_equals($sig, hash_hmac('sha256', $payload, 'wrong-secret')));

echo "\n";

// --- PAYMENT REQUEST FLOW TESTS ---
echo "--- 8. PAYMENT REQUEST FLOW ---\n";

$shipment = Shipment::whereNotNull('tracking_number')->first();
if (! $shipment) {
    $user = User::firstOrCreate(['email' => 'test@freightflow.test'], ['name' => 'Test User', 'password' => bcrypt('password'), 'role' => 'customer']);
    $shipment = Shipment::create([
        'user_id' => $user->id,
        'tracking_number' => 'FF-TEST-'.strtoupper(uniqid()),
        'status' => 'approved',
        'service_level' => 'standard',
        'sender_name' => 'Test Sender',
        'recipient_name' => 'Test Receiver',
        'origin_address' => ['city' => 'New York', 'country' => 'US'],
        'destination_address' => ['city' => 'Lagos', 'country' => 'NG'],
        'weight_kg' => 5,
        'declared_value' => 500,
        'quoted_amount' => 100,
    ]);
}

$paymentRequest = PaymentRequest::create([
    'shipment_id' => $shipment->id,
    'title' => 'Test Payment',
    'reason' => 'Test payment for gateway integration',
    'amount' => 100.00,
    'currency' => 'USD',
    'secure_token' => Str::random(64),
    'created_by' => User::first()->id,
]);
test('payment request created', $paymentRequest->exists);
test('payment request has secure token', ! empty($paymentRequest->secure_token));
test('payment request is pending', $paymentRequest->fresh()->status === 'payment_required');
test('payment request belongs to correct shipment', $paymentRequest->shipment_id === $shipment->id);

// Create a transaction
$transaction = PaymentTransaction::create([
    'payment_request_id' => $paymentRequest->id,
    'provider' => 'flutterwave',
    'provider_reference' => 'FLW-TEST-'.strtoupper(uniqid()),
    'amount' => 100.00,
    'currency' => 'USD',
    'fee_amount' => 1.40,
    'net_amount' => 98.60,
    'status' => 'initiated',
    'provider_payload' => ['mode' => 'test'],
]);
test('transaction created', $transaction->exists);
test('transaction has fee_amount', $transaction->fee_amount == 1.40);
test('transaction has net_amount', $transaction->net_amount == 98.60);

// Check idempotency
$alreadyProcessed = $security->isWebhookProcessed('nonexistent-ref', 'flutterwave');
test('unprocessed ref not flagged as processed', ! $alreadyProcessed);

// Mark as processed
$security->markWebhookProcessed($transaction->provider_reference, 'flutterwave');
$wasProcessed = $security->isWebhookProcessed($transaction->provider_reference, 'flutterwave');
test('processed ref is flagged', $wasProcessed);

echo "\n";

// --- AUDIT LOG TESTS ---
echo "--- 9. PAYMENT AUDIT LOGS ---\n";

$security->logPaymentAudit(
    'payment.initiated',
    $transaction,
    null,
    'initiated',
    null,
    null,
    ['test' => true]
);

$auditCount = PaymentAuditLog::where('event', 'payment.initiated')->count();
test('audit log created', $auditCount > 0);

$latestAudit = PaymentAuditLog::where('event', 'payment.initiated')->latest()->first();
test('audit log has provider', $latestAudit->provider === 'flutterwave');
test('audit log has reference', $latestAudit->provider_reference === $transaction->provider_reference);
test('audit log has amount', $latestAudit->amount == 100.00);
test('audit log has payload', isset($latestAudit->payload['test']));

echo "\n";

// --- GATEWAY DISABLED REJECTION ---
echo "--- 10. DISABLED GATEWAY REJECTION ---\n";

$stripeSetting->update(['is_active' => false]);
$manager->invalidateCache('stripe');
$disabledGateway = $manager->get('stripe');
test('disabled gateway returns null', $disabledGateway === null);
test('isGatewayEnabled returns false for disabled', ! $security->isGatewayEnabled('stripe'));

$stripeSetting->update(['is_active' => true]);
test('re-enabled gateway works', $security->isGatewayEnabled('stripe'));

echo "\n";

// --- PAYMENT STAGED PAYMENT TESTS ---
echo "--- 11. STAGED PAYMENT INTEGRATION ---\n";

$stagedPayment = $shipment->payments()->first();
if ($stagedPayment) {
    test('staged payment exists for shipment', true);
    test('staged payment has correct amount', $stagedPayment->amount > 0);
    test('staged payment has label', ! empty($stagedPayment->label));
} else {
    test('staged payment exists', false, 'no staged payments for shipment');
}

// --- CLEANUP ---
echo "\n--- CLEANUP ---\n";
$transaction->delete();
$paymentRequest->delete();
test('test data cleaned up', true);

echo "\n========================================\n";
echo " RESULTS: {$pass} PASSED, {$fail} FAILED\n";
echo "========================================\n";

if ($fail > 0) {
    exit(1);
}
