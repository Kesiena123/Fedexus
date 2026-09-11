<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\PaymentRequest;
use App\Models\PaymentProof;
use App\Models\BankAccount;

// Reset
$pr = PaymentRequest::where('secure_token', 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi')->first();
PaymentProof::where('payment_request_id', $pr->id)->delete();
$pr->transactions()->delete();
$pr->update(['status' => 'payment_required']);
echo "Reset PR #{$pr->id} to pending\n";

$base = 'http://127.0.0.1:8000';
$token = $pr->secure_token;
$cjar = sys_get_temp_dir() . '/ff_e2e_cjar';
@unlink($cjar);

// Step 1: GET 
$ch = curl_init("$base/pay/$token");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cjar, CURLOPT_TIMEOUT => 10]);
$html = curl_exec($ch);
$http1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "1. GET payment page -> HTTP $http1\n";

preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);
$csrf = $m[1];

// Debug: check cookie file contents
$cookieContent = file_get_contents($cjar);
echo "   Cookie file: " . str_replace("\n", " ", trim($cookieContent)) . "\n";

// Step 2: POST bank_transfer (non-AJAX) - use same handle to keep session
$ch2 = curl_init("$base/pay/$token");
$cookieStr = '';
if (preg_match('/freightflow_session\s+(\S+)/', $cookieContent, $mc)) {
    $cookieStr = 'freightflow_session=' . $mc[1];
    echo "   Using cookie: $cookieStr\n";
}
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIE => $cookieStr,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $csrf, 'provider' => 'bank_transfer']),
    CURLOPT_TIMEOUT => 15,
]);
$resp = curl_exec($ch2);
$http2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
echo "2. POST bank_transfer -> HTTP $http2\n";
echo "   " . (strpos($resp, 'Complete Your Payment') !== false ? 'PASS: Instructions page' : 'FAIL') . "\n";

// Step 3: Extract new CSRF from instructions page
preg_match('/name="_token"\s+value="([^"]+)"/', $resp, $m2);
$csrf2 = $m2[1];
preg_match('/value="([^"]+)"[^>]*id="selectedBankId"/', $resp, $mBank);
$bankId = $mBank[1] ?? '1';
preg_match('/value="([^"]+)"[^>]*name="payment_reference"/', $resp, $mRef);
$ref = $mRef[1] ?? '';

echo "   Payment Reference: $ref\n";
echo "   Bank ID: $bankId\n";

// Create valid PNG file for upload
$testFile = sys_get_temp_dir() . '/test-proof-e2e.png';
file_put_contents($testFile, base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
));

// Step 3: Upload proof
$ch3 = curl_init("$base/pay/$token/upload-proof");
curl_setopt_array($ch3, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cjar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        '_token' => $csrf2,
        'payment_reference' => $ref ?: 'PAY-E2E-TEST',
        'notes' => 'E2E test from PHP script',
        'proof_file' => new CURLFile($testFile, 'image/png', 'proof.png'),
    ],
    CURLOPT_TIMEOUT => 30,
]);
$uploadResp = curl_exec($ch3);
$http3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);
echo "3. UPLOAD proof -> HTTP $http3\n";

if ($http3 === 302 || strpos($uploadResp, 'success') !== false || strpos($uploadResp, 'Payment proof uploaded') !== false) {
    echo "   PASS: Upload successful!\n";
} elseif (strpos($uploadResp, 'Page Expired') !== false) {
    echo "   FAIL: CSRF mismatch on upload\n";
    echo "   " . substr($uploadResp, 0, 200) . "\n";
} else {
    echo "   RESPONSE: " . substr($uploadResp, 0, 300) . "\n";
}

// Step 4: Check PaymentProof in DB
$proofs = PaymentProof::where('payment_request_id', $pr->id)->get();
echo "4. DB Check: " . $proofs->count() . " proof(s)\n";
foreach ($proofs as $p) {
    echo "   Proof #{$p->id}: ref={$p->payment_reference} status={$p->status} file={$p->original_filename}\n";
}

echo "\n=== E2E COMPLETE ===\n";
