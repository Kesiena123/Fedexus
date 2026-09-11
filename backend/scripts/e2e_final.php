<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

use App\Models\PaymentRequest;
use App\Models\PaymentTransaction;

$token = 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi';
$baseUrl = 'http://127.0.0.1:8000';

$pr = PaymentRequest::where('secure_token', $token)->first();
PaymentTransaction::where('payment_request_id', $pr->id)->delete();
$pr->update(['status' => 'payment_required']);
$pr->refresh();
echo "=== RESET: PR={$pr->id}, amount={$pr->amount} {$pr->currency} ===\n\n";

$cookieJar = tempnam(sys_get_temp_dir(), 'ff');

echo "=== Step 1: GET payment page ===\n";
$ch1 = curl_init("$baseUrl/pay/$token");
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_TIMEOUT => 15,
]);
$html = curl_exec($ch1);
$status1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);
echo "Status: {$status1}\n";

if (!preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
    echo "FAIL: No CSRF token\n"; exit(1);
}
$csrf = $m[1];
echo "CSRF: OK\n";

echo "\n=== Step 2: AJAX POST ===\n";
$ch2 = curl_init("$baseUrl/pay/$token");
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $csrf, 'provider' => 'flutterwave']),
    CURLOPT_HTTPHEADER => [
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
]);
$resp = curl_exec($ch2);
$status2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
echo "Status: {$status2}\n";

$json = json_decode($resp, true);
if (!$json) {
    echo "Response: " . substr($resp, 0, 500) . "\n";
    exit(1);
}

echo "Has redirect_url: " . (isset($json['redirect_url']) ? 'YES' : 'NO') . "\n";
echo "Has inline_config: " . (isset($json['inline_config']) ? 'YES' : 'NO') . "\n";

if (isset($json['redirect_url'])) {
    echo "redirect_url: {$json['redirect_url']}\n";
}

if (isset($json['inline_config'])) {
    $ic = $json['inline_config'];
    echo "\n=== INLINE CONFIG ===\n";
    echo "public_key: {$ic['public_key']}\n";
    echo "tx_ref: {$ic['tx_ref']}\n";
    echo "amount: {$ic['amount']}\n";
    echo "currency: {$ic['currency']}\n";
    echo "customer name: {$ic['customer']['name']}\n";
    echo "customer email: {$ic['customer']['email']}\n";
    echo "callback_url: {$ic['callback_url']}\n";
    echo "\n=== INLINE CONFIG VERIFIED! ===\n";
    echo "Frontend will call FlutterwaveCheckout() popup instead of redirect.\n";
}

echo "\n=== DONE ===\n";
@unlink($cookieJar);
