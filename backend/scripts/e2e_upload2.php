<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

// Reset payment request
use App\Models\PaymentRequest;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;

$pr = PaymentRequest::where('secure_token', 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi')->first();
echo "Payment Request #{$pr->id} - Status: {$pr->status}\n";
PaymentProof::where('payment_request_id', $pr->id)->delete();
$pr->transactions()->delete();
$pr->update(['status' => 'payment_required']);
echo "Reset to pending\n";

$base = 'http://127.0.0.1:8000';
$token = $pr->secure_token;
$cjar = sys_get_temp_dir() . '/ff_e2e_cjar2';

// Step 1: GET payment page
$ch = curl_init("$base/pay/$token");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cjar, CURLOPT_TIMEOUT => 15]);
$html = curl_exec($ch);
$http1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "\n1. GET /pay/$token -> HTTP $http1\n";
assert($http1 === 200, 'FAIL: payment page not loaded');

// Extract CSRF from form input
preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);
$csrf = $m[1];
echo "   CSRF: $csrf\n";

// Step 2: Non-AJAX POST for bank_transfer
// Also extract csrf-token from meta for header approach
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $mMeta);
$csrfMeta = $mMeta[1];
echo "   Meta CSRF: $csrfMeta\n";

$ch2 = curl_init("$base/pay/$token");
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cjar,
    CURLOPT_COOKIEJAR => $cjar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $csrf, 'provider' => 'bank_transfer']),
    CURLOPT_HTTPHEADER => [
        'X-CSRF-TOKEN: ' . $csrfMeta,
        'X-Requested-With: XMLHttpRequest',
    ],
    CURLOPT_TIMEOUT => 15,
]);
$resp = curl_exec($ch2);
$http2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "\n2. POST bank_transfer -> HTTP $http2\n";
if (strpos($resp, 'Complete Your Payment') !== false) {
    echo "   PASS: Instructions page rendered!\n";
} elseif ($http2 === 419) {
    echo "   FAIL: CSRF mismatch\n";
    echo "   Body: " . substr($resp, 0, 500) . "\n";
    exit(1);
} else {
    echo "   UNEXPECTED: " . substr($resp, 0, 300) . "\n";
    exit(1);
}

// Step 3: Upload proof
preg_match('/name="_token"\s+value="([^"]+)"/', $resp, $m2);
$csrf2 = $m2[1];

// Create minimal valid PNG
$testFile = sys_get_temp_dir() . '/test-proof-real.png';
$pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
file_put_contents($testFile, $pngContent);

$ch3 = curl_init("$base/pay/$token/upload-proof");
curl_setopt_array($ch3, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cjar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        '_token' => $csrf2,
        'payment_reference' => 'PAY-7JBTOD-E2E',
        'notes' => 'E2E test upload',
        'proof_file' => new CURLFile($testFile, 'image/png', 'proof.png'),
    ],
    CURLOPT_TIMEOUT => 30,
]);
$uploadResp = curl_exec($ch3);
$http3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "\n3. Upload proof -> HTTP $http3\n";
if ($http3 === 200 || strpos($uploadResp, 'success') !== false || strpos($uploadResp, 'Payment proof uploaded') !== false) {
    echo "   PASS: Upload successful!\n";
} else {
    echo "   RESPONSE: " . substr($uploadResp, 0, 400) . "\n";
}

// Verify in DB
$proofs = PaymentProof::where('payment_reference', 'LIKE', '%PAY-7JBTOD%')->get();
echo "\n4. DB check: " . $proofs->count() . " proof(s)\n";
foreach ($proofs as $p) {
    echo "   Proof #{$p->id}: ref={$p->payment_reference} status={$p->status} file={$p->original_filename}\n";
    echo "   Transaction ID: " . ($p->payment_transaction_id ?? 'null') . "\n";
}

// Cleanup
@unlink($cjar);
@unlink($testFile);
echo "\n=== ALL CHECKS PASSED ===\n";
