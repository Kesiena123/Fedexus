<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$base = 'http://127.0.0.1:8000';
$token = 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi';

function extractCookies(&$cookies, $headers) {
    foreach ($headers as $h) {
        if (preg_match('/^Set-Cookie:\s*(freightflow_session|XSRF-TOKEN)=([^;]+)/i', $h, $m)) {
            $found = false;
            foreach ($cookies as &$c) {
                if (strpos($c, $m[1] . '=') === 0) { $c = $m[1] . '=' . $m[2]; $found = true; break; }
            }
            if (!$found) $cookies[] = $m[1] . '=' . $m[2];
        }
    }
}

function httpGet($url, &$cookies) {
    $opts = ['http' => ['method' => 'GET', 'header' => "Cookie: " . implode('; ', $cookies), 'ignore_errors' => true]];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return [false, 0, ''];
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $ms);
    $status = (int)($ms[1] ?? 0);
    extractCookies($cookies, $http_response_header);
    return [$body, $status];
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
    $body = @file_get_contents($url, false, $ctx);
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $ms);
    $status = (int)($ms[1] ?? 0);
    extractCookies($cookies, $http_response_header);
    return [$body, $status, $http_response_header];
}

function httpMultipart($url, $fields, $files, &$cookies) {
    $boundary = '----TestBoundary' . md5(uniqid());
    $body = '';
    foreach ($fields as $k => $v) $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n$v\r\n";
    foreach ($files as $k => $f) {
        $body .= "--$boundary\r\nContent-Disposition: form-data; name=\"$k\"; filename=\"{$f['name']}\"\r\nContent-Type: {$f['type']}\r\n\r\n{$f['content']}\r\n";
    }
    $body .= "--$boundary--\r\n";
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: multipart/form-data; boundary=$boundary\r\nCookie: " . implode('; ', $cookies),
            'content' => $body,
            'ignore_errors' => true,
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $ms);
    $status = (int)($ms[1] ?? 0);
    extractCookies($cookies, $http_response_header);
    return [$resp, $status, $http_response_header];
}

// Create or reset a payment request fixture
$shipment = \App\Models\Shipment::find(19);
if (!$shipment) {
    die("ERROR: Shipment #19 not found. Run database seeder.\n");
}
$paymentRequest = \App\Models\PaymentRequest::where('secure_token', $token)->first();
if (!$paymentRequest) {
    $paymentRequest = \App\Models\PaymentRequest::create([
        'shipment_id' => $shipment->id,
        'secure_token' => $token,
        'title' => 'E2E Test Payment',
        'reason' => 'Comprehensive end-to-end test fixture',
        'amount' => 500.00,
        'currency' => 'USD',
        'status' => 'payment_required',
        'due_at' => now()->addDays(7),
        'requested_method' => 'bank_transfer',
    ]);
    echo "Created payment request #{$paymentRequest->id} with fixture token\n";
} else {
    $paymentRequest->update(['status' => 'payment_required', 'metadata' => null]);
    echo "Reset payment request #{$paymentRequest->id} status to 'payment_required'\n";
}
// Delete any existing transactions for this payment request from previous runs
\App\Models\PaymentTransaction::where('payment_request_id', $paymentRequest->id)->delete();
\App\Models\PaymentProof::where('payment_request_id', $paymentRequest->id)->delete();
echo "Cleared previous transactions and proofs\n\n";

$pass = 0; $fail = 0;
function check($label, $cond, $detail = '') { global $pass, $fail; if ($cond) { $pass++; echo "  PASS: $label\n"; } else { $fail++; echo "  FAIL: $label" . ($detail ? " - $detail" : '') . "\n"; } }

echo "=== COMPREHENSIVE E2E TEST ===\n\n";

// ============= PUBLIC PAGES =============
echo "--- Public Pages ---\n";
$c = [];
list($b, $s) = httpGet($base . '/', $c); check('Home page', $s === 200);
list($b, $s) = httpGet($base . '/about', $c); check('About page', $s === 200);
list($b, $s) = httpGet($base . '/services', $c); check('Services page', $s === 200);
list($b, $s) = httpGet($base . '/pricing', $c); check('Pricing page', $s === 200);
list($b, $s) = httpGet($base . '/contact', $c); check('Contact page', $s === 200);
list($b, $s) = httpGet($base . '/tracking', $c); check('Tracking page', $s === 200);
list($b, $s) = httpGet($base . '/faq', $c); check('FAQ page', $s === 200);
list($b, $s) = httpGet($base . '/support', $c); check('Support page', $s === 200);

// ============= PAYMENT REQUEST PAGE =============
echo "\n--- Payment Request Page ---\n";
$c = [];
list($b, $s) = httpGet($base . '/pay/' . $token, $c);
check('Payment page accessible', $s === 200);
check('Shows payment methods', strpos($b, 'bank_transfer') !== false || strpos($b, 'Bank Transfer') !== false);
check('Has CSRF token', preg_match('/name="_token"\s+value="([^"]+)"/', $b, $m) === 1);
$csrf = $m[1] ?? '';
if (empty($csrf)) { echo "  WARNING: No CSRF token extracted from payment page\n"; }

// ============= BANK TRANSFER CHECKOUT =============
echo "\n--- Bank Transfer Checkout ---\n";
$c = []; // Fresh cookies
list($b, $s) = httpGet($base . '/pay/' . $token, $c); check('GET payment page', $s === 200);
preg_match('/name="_token"\s+value="([^"]+)"/', $b, $m);
$csrf2 = $m[1] ?? '';
if (empty($csrf2)) { echo "  WARNING: No CSRF token for checkout POST\n"; }
list($b, $s) = httpPost($base . '/pay/' . $token, ['_token' => $csrf2, 'provider' => 'bank_transfer'], $c);
check('Bank transfer checkout returns 200', $s === 200);
check('Instructions page rendered', strpos($b, 'Complete Your Payment') !== false);
check('Shows payment reference', preg_match('/PAY-[\w-]+/', $b, $mRef) === 1);
$ref = $mRef[0] ?? 'N/A';
echo "  Payment Ref: $ref\n";

// ============= PROOF UPLOAD =============
echo "\n--- Proof Upload ---\n";
preg_match('/name="_token"\s+value="([^"]+)"/', $b, $m);
$csrf3 = $m[1] ?? '';
if (empty($csrf3)) { echo "  WARNING: No CSRF token for proof upload\n"; }
$testFile = sys_get_temp_dir() . '/e2e-upload-final.png';
file_put_contents($testFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAJRU5ErkJggg=='));
list($b, $s, $rh) = httpMultipart($base . '/pay/' . $token . '/upload-proof', [
    '_token' => $csrf3, 'payment_reference' => 'PAY-E2E-' . rand(100,999), 'notes' => 'E2E test'
], ['proof_file' => ['name' => 'proof.png', 'type' => 'image/png', 'content' => file_get_contents($testFile)]], $c);
check('Upload returns 302 redirect (or 200)', $s === 302 || $s === 303 || $s === 200);
// Verify upload by checking database
$proofRecord = \App\Models\PaymentProof::where('payment_request_id', $paymentRequest->id)->latest()->first();
check('Proof stored in database', $proofRecord !== null && $proofRecord->status === 'pending');
check('File hash generated', !empty($proofRecord?->file_hash));

// ============= ADMIN PAGES (with login via curl) =============
echo "\n--- Admin Pages ---\n";
$ckfile = tempnam(sys_get_temp_dir(), 'CURLCOOKIE');

// GET login page
$ch = curl_init("$base/admin/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $ckfile, CURLOPT_COOKIEFILE => $ckfile,
    CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true,
]);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
check('Admin login page', $httpcode === 200);
check('Shows login form', stripos($resp, 'Admin') !== false || stripos($resp, 'Login') !== false || stripos($resp, 'Password') !== false);
curl_close($ch);

// POST login
preg_match('/name="_token"\s+value="([^"]+)"/', $resp, $m);
$ch = curl_init("$base/admin/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $m[1], 'email' => 'admin@freightflow.test', 'password' => 'Password123!']),
    CURLOPT_COOKIEJAR => $ckfile, CURLOPT_COOKIEFILE => $ckfile,
    CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true,
]);
$resp = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
check('Admin login succeeds', $httpcode === 302);
echo "  Login response status: $httpcode\n";
curl_close($ch);

// Check admin pages after login
$adminPages = [
    '/admin' => 'Admin Dashboard',
    '/admin/shipments' => 'Shipments',
    '/admin/payments' => 'Payments',
    '/admin/settings/payment' => 'Payment Settings',
    '/admin/settings/bank-accounts' => 'Bank Accounts',
    '/admin/bank-transfers' => 'Bank Transfers',
    '/admin/settings/account' => 'Account Settings',
    '/admin/audit-logs' => 'Audit Logs',
];
foreach ($adminPages as $path => $label) {
    $ch = curl_init("$base$path");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $ckfile,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true,
    ]);
    curl_exec($ch);
    $s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    check("$label ($path)", $s === 200);
    curl_close($ch);
}
unlink($ckfile);

// ============= TRACKING PAGES =============
echo "\n--- Tracking Pages ---\n";
list($b, $s) = httpGet($base . '/tracking/GEX-2026-S57JBTOD', $c); check('Tracking GEX-2026-S57JBTOD', $s === 200);
list($b, $s) = httpGet($base . '/receipt/GEX-2026-S57JBTOD', $c); check('Receipt page', $s === 200);

// ============= SUMMARY =============
echo "\n=== RESULTS ===\n";
$total = $pass + $fail;
echo "  Total: $total | Pass: $pass | Fail: $fail\n";
echo ($fail === 0 ? "  ALL PASSED\n" : "  FAILURES DETECTED\n");
