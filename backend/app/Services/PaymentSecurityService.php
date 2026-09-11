<?php

namespace App\Services;

use App\Models\PaymentAuditLog;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentSecurityService
{
    public function verifyWebhookSignature(string $payload, string $secret, string $algorithm = 'sha256'): bool
    {
        if (empty($secret)) {
            Log::warning('[PaymentSecurity] Webhook secret is empty.');

            return false;
        }

        $signature = request()->header('X-Payment-Signature')
            ?? request()->header('X-Flutterwave-Signature')
            ?? request()->header('Stripe-Signature')
            ?? request()->header('PayPal-Transmission-Sig')
            ?? request()->header('x-nowpayments-sig')
            ?? request()->input('signature', '');

        if (empty($signature)) {
            Log::warning('[PaymentSecurity] No webhook signature found in request.');

            return false;
        }

        $expected = hash_hmac($algorithm, $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function isDuplicateTransaction(string $reference): bool
    {
        if (empty($reference)) {
            return false;
        }

        return PaymentTransaction::where('provider_reference', $reference)
            ->whereIn('status', ['verified', 'paid'])
            ->exists();
    }

    public function isWebhookProcessed(string $reference, string $provider): bool
    {
        if (empty($reference)) {
            return false;
        }

        $cacheKey = "webhook_processed:{$provider}:{$reference}";
        if (Cache::has($cacheKey)) {
            return true;
        }

        $exists = PaymentTransaction::where('provider_reference', $reference)
            ->where('provider', $provider)
            ->where('webhook_received_at', '!=', null)
            ->exists();

        if ($exists) {
            Cache::put($cacheKey, true, 3600);
        }

        return $exists;
    }

    public function markWebhookProcessed(string $reference, string $provider): void
    {
        $cacheKey = "webhook_processed:{$provider}:{$reference}";
        Cache::put($cacheKey, true, 3600);

        PaymentTransaction::where('provider_reference', $reference)
            ->where('provider', $provider)
            ->update(['webhook_received_at' => now()]);
    }

    public function validateAmount(float $expectedAmount, float $receivedAmount, float $tolerance = 0.01): bool
    {
        return abs($expectedAmount - $receivedAmount) <= $tolerance;
    }

    public function validateCurrency(string $expectedCurrency, string $receivedCurrency): bool
    {
        return strtoupper($expectedCurrency) === strtoupper($receivedCurrency);
    }

    public function canAccessPaymentRequest(PaymentRequest $paymentRequest, ?string $trackingNumber = null): bool
    {
        if (empty($trackingNumber)) {
            return true;
        }

        return $paymentRequest->shipment->tracking_number === $trackingNumber;
    }

    public function isGatewayEnabled(string $gatewayName): bool
    {
        return PaymentSetting::where('gateway_name', $gatewayName)
            ->where('is_active', true)
            ->exists();
    }

    public function logPaymentAudit(
        string $event,
        ?PaymentTransaction $transaction = null,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?Request $request = null,
        ?int $adminUserId = null,
        array $extraPayload = []
    ): void {
        $payload = array_merge($extraPayload, [
            'timestamp' => now()->toIso8601String(),
        ]);

        PaymentAuditLog::create([
            'payment_transaction_id' => $transaction?->id,
            'event' => $event,
            'provider' => $transaction?->provider,
            'provider_reference' => $transaction?->provider_reference,
            'amount' => $transaction?->amount,
            'currency' => $transaction?->currency,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'admin_user_id' => $adminUserId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 500) : null,
            'payload' => $payload,
        ]);
    }

    public function calculateFee(float $amount, float $feePercentage, float $fixedFee = 0): array
    {
        $percentFee = round($amount * ($feePercentage / 100), 2);
        $totalFee = round($percentFee + $fixedFee, 2);
        $netAmount = round($amount - $totalFee, 2);

        return [
            'fee_amount' => max(0, $totalFee),
            'net_amount' => max(0, $netAmount),
        ];
    }
}
