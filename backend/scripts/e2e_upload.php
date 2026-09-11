<?php
$base = 'http://127.0.0.1:8000';
$token = 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi';
$cjar = sys_get_temp_dir() . '/ff_e2e_cjar';

echo "=== E2E: Bank Transfer Full Workflow ===\n\n";

// Step 1: GET payment page
$ch = curl_init("$base/pay/$token");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cjar,
    CURLOPT_TIMEOUT => 15,
]);
$html = curl_exec($ch);
curl_close($ch);

if (strpos($html, 'Secure payment request') !== false) {
    echo "Step 1: GET payment page ... OK\n";
} else {
    echo "Step 1: FAIL - page not loaded\n"; exit(1);
}

preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);
$csrf = $m[1];
echo "  CSRF: $csrf\n";

// Step 2: POST as non-AJAX (standard form submit) for bank_transfer
$ch2 = curl_init("$base/pay/$token");
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cjar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $csrf, 'provider' => 'bank_transfer']),
    CURLOPT_TIMEOUT => 15,
]);
$resp = curl_exec($ch2);
$info2 = curl_getinfo($ch2);
curl_close($ch2);

echo "Step 2: POST bank_transfer ... HTTP {$info2['http_code']}\n";

if (strpos($resp, 'Complete Your Payment') !== false) {
    echo "  -> Bank Transfer Instructions page RENDERED (PASS)\n";
} elseif (strpos($resp, 'show_instructions') !== false) {
    echo "  -> Got JSON response (AJAX path) — re-testing with AJAX headers\n";
    preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m2);
    $csrf2 = $m2[1];

    $ch3 = curl_init("$base/pay/$token");
    curl_setopt_array($ch3, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cjar,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['_token' => $csrf2, 'provider' => 'bank_transfer']),
        CURLOPT_HTTPHEADER => [
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $jsonResp = curl_exec($ch3);
    $info3 = curl_getinfo($ch3);
    curl_close($ch3);

    $data = json_decode($jsonResp, true);
    if ($data && isset($data['show_instructions'])) {
        echo "  -> AJAX JSON response: show_instructions=true (PASS)\n";
        echo "  -> Reference: {$data['reference']}\n";
        echo "  -> Bank accounts: " . count($data['bank_accounts']) . "\n";
        foreach ($data['bank_accounts'] as $ba) {
            echo "     - {$ba['bank_name']} ({$ba['supported_currency']})\n";
        }
    } else {
        echo "  -> AJAX response unexpected: " . substr($jsonResp, 0, 200) . "\n";
    }
} else {
    echo "  -> Response: HTTP {$info2['http_code']} - " . substr($resp, 0, 200) . "\n";
}

// Step 3: Upload proof of payment
echo "\nStep 3: Upload proof...\n";

// Create a simple test file (valid PNG-like content for MIME detection)
$testFile = sys_get_temp_dir() . '/test-proof.png';
if (!file_exists($testFile)) {
    // Minimal valid PNG (1x1 pixel transparent)
    file_put_contents($testFile, "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 100));
}

// Need fresh cookie + CSRF for upload
$ch4 = curl_init("$base/pay/$token");
curl_setopt_array($ch4, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cjar,
    CURLOPT_TIMEOUT => 15,
]);
$html4 = curl_exec($ch4);
curl_close($ch4);
preg_match('/name="_token"\s+value="([^"]+)"/', $html4, $m4);
$csrf4 = $m4[1];

$ch5 = curl_init("$base/pay/$token/upload-proof");
curl_setopt_array($ch5, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cjar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        '_token' => $csrf4,
        'payment_reference' => 'PAY-7JBTOD-TEST',
        'notes' => 'Test transfer from savings account',
        'proof_file' => new CURLFile($testFile, 'image/png', 'proof.png'),
    ],
    CURLOPT_TIMEOUT => 30,
]);
$uploadResp = curl_exec($ch5);
$info5 = curl_getinfo($ch5);
curl_close($ch5);

echo "  Upload HTTP status: {$info5['http_code']}\n";
if (strpos($uploadResp, 'success') !== false || strpos($uploadResp, 'Payment proof uploaded') !== false) {
    echo "  -> UPLOAD SUCCESSFUL (PASS)\n";
} elseif (strpos($uploadResp, 'This payment request is not accepting uploads') !== false) {
    echo "  -> UPLOAD REJECTED: check status\n";
    echo "  Response: " . substr($uploadResp, 0, 300) . "\n";
} else {
    echo "  Response: " . substr($uploadResp, 0, 500) . "\n";
}

// Step 4: Check PaymentProof was created
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\PaymentProof;
use App\Models\BankAccount;

$proofs = PaymentProof::where('payment_reference', 'like', '%PAY-7JBTOD%')->get();
echo "\nStep 4: Verify PaymentProof records...\n";
echo "  Found: {$proofs->count()} proof(s)\n";
foreach ($proofs as $p) {
    echo "  - ID:{$p->id} Status:{$p->status} Ref:{$p->payment_reference} File:{$p->original_filename}\n";
}

echo "\n=== E2E COMPLETE ===\n";
@unlink($cjar);
