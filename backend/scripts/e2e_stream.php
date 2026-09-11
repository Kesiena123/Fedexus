<?php
// Direct HTTP test using PHP streams (no curl cookie issues)
$base = 'http://127.0.0.1:8000';
$token = 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi';

function httpGet($url, &$cookies) {
    $opts = ['http' => ['method' => 'GET', 'header' => "Cookie: " . implode('; ', $cookies)]];
    $ctx = stream_context_create($opts);
    $body = file_get_contents($url, false, $ctx);
    foreach ($http_response_header as $h) {
        if (preg_match('/^Set-Cookie:\s*(freightflow_session|XSRF-TOKEN)=([^;]+)/i', $h, $m)) {
            $found = false;
            foreach ($cookies as &$c) {
                if (strpos($c, $m[1] . '=') === 0) { $c = $m[1] . '=' . $m[2]; $found = true; break; }
            }
            if (!$found) $cookies[] = $m[1] . '=' . $m[2];
        }
    }
    return $body;
}

function httpPost($url, $data, &$cookies) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: " . implode('; ', $cookies),
            'content' => http_build_query($data),
            'ignore_errors' => true,
        ]
    ];
    $ctx = stream_context_create($opts);
    $body = file_get_contents($url, false, $ctx);
    return [$body, $http_response_header];
}

// Reset state
require __DIR__.'/../vendor/autoload.php';
$appr = require_once __DIR__.'/../bootstrap/app.php';
$appr->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();
$pr = \App\Models\PaymentRequest::where('secure_token', $token)->first();
if (!$pr) {
    $shipment = \App\Models\Shipment::find(19);
    if ($shipment) {
        $pr = \App\Models\PaymentRequest::create([
            'shipment_id' => $shipment->id,
            'secure_token' => $token,
            'title' => 'Stream E2E Payment',
            'reason' => 'Stream test fixture',
            'amount' => 750.00,
            'currency' => 'USD',
            'status' => 'payment_required',
            'due_at' => now()->addDays(7),
            'requested_method' => 'bank_transfer',
        ]);
        echo "Created payment request #{$pr->id} for stream test\n";
    }
} elseif ($pr->status !== 'payment_required') {
    $pr->update(['status' => 'payment_required']);
    echo "Reset payment request to payment_required\n";
}
\App\Models\PaymentTransaction::where('payment_request_id', $pr?->id)->delete();
\App\Models\PaymentProof::whereHas('paymentRequest', function($q) use ($pr) { $q->where('id', $pr?->id); })->delete();

$cookies = [];

// Step 1: GET payment page
$html = httpGet("$base/pay/$token", $cookies);
echo "1. GET -> " . (strpos($html, 'Secure payment request') !== false ? 'OK' : 'FAIL') . "\n";
echo "   Cookies: " . implode(', ', $cookies) . "\n";

preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);
$csrf = $m[1];
echo "   CSRF: $csrf\n";

// Step 2: POST bank_transfer
list($resp, $headers) = httpPost("$base/pay/$token", ['_token' => $csrf, 'provider' => 'bank_transfer'], $cookies);
$statusLine = $headers[0] ?? '';
echo "2. POST -> $statusLine\n";
echo "   " . (strpos($resp, 'Complete Your Payment') !== false ? 'PASS: Instructions page' : 'UNEXPECTED') . "\n";

if (strpos($resp, 'Complete Your Payment') === false) {
    echo "   Response: " . substr($resp, 0, 300) . "\n";
    exit(1);
}

// Step 3: Extract token from instructions page
preg_match('/name="_token"\s+value="([^"]+)"/', $resp, $m2);
$csrf2 = $m2[1] ?? $csrf;

// Create test file for upload
$testFile = sys_get_temp_dir() . '/test-png-e2e.png';
file_put_contents($testFile, base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
));

// Step 3: Upload proof (multipart)
$boundary = '----WebKitFormBoundary' . md5(uniqid());
$postBody = "--$boundary\r\nContent-Disposition: form-data; name=\"_token\"\r\n\r\n$csrf2\r\n";
$postBody .= "--$boundary\r\nContent-Disposition: form-data; name=\"payment_reference\"\r\n\r\nPAY-STREAM-E2E\r\n";
$postBody .= "--$boundary\r\nContent-Disposition: form-data; name=\"notes\"\r\n\r\nE2E stream test\r\n";
$fileContent = file_get_contents($testFile);
$postBody .= "--$boundary\r\nContent-Disposition: form-data; name=\"proof_file\"; filename=\"proof.png\"\r\nContent-Type: image/png\r\n\r\n$fileContent\r\n";
$postBody .= "--$boundary--\r\n";

$opts3 = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: multipart/form-data; boundary=$boundary\r\nCookie: " . implode('; ', $cookies),
        'content' => $postBody,
        'ignore_errors' => true,
    ]
];
$ctx3 = stream_context_create($opts3);
$uploadResp = file_get_contents("$base/pay/$token/upload-proof", false, $ctx3);

echo "3. UPLOAD -> " . (strpos($uploadResp, 'Payment proof uploaded') !== false ? 'PASS: Upload successful!' : 'FAIL') . "\n";
if (strpos($uploadResp, 'Payment proof uploaded') === false) {
    if (strpos($uploadResp, 'Page Expired') !== false) echo "   Reason: CSRF mismatch\n";
    else echo "   Response: " . substr($uploadResp, 0, 300) . "\n";
}

// Verify DB
use App\Models\PaymentProof;
$proofs = PaymentProof::where('payment_reference', 'PAY-STREAM-E2E')->get();
echo "4. DB: " . $proofs->count() . " proof(s)\n";
foreach ($proofs as $p) {
    echo "   Proof #{$p->id}: ref={$p->payment_reference} status={$p->status}\n";
}

echo "\n=== DONE ===\n";
