<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlutterwaveGateway extends BaseGateway
{
    protected string $name = 'flutterwave';

    protected string $displayName = 'Flutterwave';

    private function getApiBase(): string
    {
        return config('services.flutterwave.base_url');
    }

    public function getLogoUrl(): string
    {
        return 'https://res.cloudinary.com/paystack/image/upload/v1605640621/flutterwave_logo.svg';
    }

    public function getSupportedCurrencies(): array
    {
        return ['NGN', 'USD', 'GHS', 'KES', 'ZAR', 'GBP', 'EUR', 'UGX', 'TZS', 'RWF'];
    }

    public function getFeePercentage(): float
    {
        return 1.4;
    }

    public function getFixedFee(): float
    {
        return 0.0;
    }

    public function getCheckoutFields(): array
    {
        return [];
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function createCheckout(PaymentRequest $paymentRequest, PaymentTransaction $transaction, Request $httpRequest): array
    {
        $this->ensureConfiguredForCheckout();

        $redirectUrl = route('payment.callback', [
            'token' => $paymentRequest->secure_token,
            'provider' => 'flutterwave',
        ]);

        $this->logGateway('info', 'Creating checkout', [
            'reference' => $transaction->provider_reference,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'redirect_url' => $redirectUrl,
            'mode' => $this->setting->mode,
        ]);

        $payload = [
            'tx_ref' => $transaction->provider_reference,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'redirect_url' => $redirectUrl,
            'meta' => [
                'payment_request_id' => $paymentRequest->id,
                'tracking_number' => $paymentRequest->shipment->tracking_number ?? '',
                'secure_token' => $paymentRequest->secure_token,
            ],
            'customer' => [
                'name' => $paymentRequest->shipment->recipient_name ?? 'Guest Customer',
                'email' => $this->resolveCustomerEmail($paymentRequest),
            ],
            'customizations' => [
                'title' => $paymentRequest->title,
                'description' => $paymentRequest->reason,
            ],
        ];

        $response = $this->apiPost('/payments', $payload);
        $body = $response->json();

        $this->logGateway('info', 'Checkout API response', [
            'reference' => $transaction->provider_reference,
            'http_status' => $response->status(),
            'api_status' => $body['status'] ?? null,
            'link' => $body['data']['link'] ?? null,
            'message' => $body['message'] ?? null,
        ]);

        if (! $response->successful() || ($body['status'] ?? '') !== 'success') {
            $errorMsg = $body['message'] ?? 'Flutterwave API returned an error';
            $this->logGateway('error', 'Checkout creation failed', [
                'reference' => $transaction->provider_reference,
                'http_status' => $response->status(),
                'response' => $body,
            ]);
            abort(422, "Payment initialization failed: {$errorMsg}");
        }

        $checkoutLink = $body['data']['link'] ?? '';

        if (empty($checkoutLink)) {
            $this->logGateway('error', 'Checkout link is empty', [
                'reference' => $transaction->provider_reference,
                'response' => $body,
            ]);
            abort(422, 'Payment initialization failed: Flutterwave returned an empty checkout link.');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                'flutterwave_tx_id' => $body['data']['id'] ?? null,
                'flutterwave_tx_ref' => $body['data']['tx_ref'] ?? null,
                'flutterwave_link' => $checkoutLink,
                'checkout_created_at' => now()->toIso8601String(),
            ]),
        ]);

        $this->logGateway('info', 'Checkout created successfully', [
            'reference' => $transaction->provider_reference,
            'checkout_url' => $checkoutLink,
        ]);

        $callbackUrl = route('payment.callback', [
            'token' => $paymentRequest->secure_token,
            'provider' => 'flutterwave',
        ]);

        return [
            'redirect_url' => $checkoutLink,
            'provider_reference' => $transaction->provider_reference,
            'inline_config' => [
                'public_key' => $this->setting->public_key,
                'tx_ref' => $transaction->provider_reference,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'customer' => [
                    'name' => $paymentRequest->shipment->recipient_name ?? 'Guest Customer',
                    'email' => $this->resolveCustomerEmail($paymentRequest),
                ],
                'customizations' => [
                    'title' => $paymentRequest->title,
                    'description' => $paymentRequest->reason,
                ],
                'callback_url' => $callbackUrl,
            ],
        ];
    }

    public function verifyTransaction(PaymentTransaction $transaction): array
    {
        $this->ensureSecretKeyConfigured();

        $txId = $transaction->provider_payload['flutterwave_tx_id'] ?? null;

        if (! $txId) {
            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'Flutterwave transaction ID is missing, so the payment could not be verified.',
            ];
        }

        try {
            $response = $this->apiGet("/transactions/{$txId}/verify");
        } catch (\Throwable $e) {
            $this->logGateway('warning', 'Transaction verification request failed', [
                'reference' => $transaction->provider_reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'Transaction verification request failed with Flutterwave.',
            ];
        }

        $body = $response->json();

        if (! $response->successful() || ($body['status'] ?? '') !== 'success') {
            $this->logGateway('warning', 'Transaction verification failed', [
                'reference' => $transaction->provider_reference,
                'response' => $body,
            ]);

            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'Transaction verification failed with Flutterwave.',
            ];
        }

        $data = $body['data'] ?? [];
        $fwStatus = strtolower($data['status'] ?? '');
        $isSuccessful = in_array($fwStatus, ['successful', 'completed'], true);

        $amountMatch = abs((float) ($data['amount'] ?? 0) - (float) $transaction->amount) < 0.01;
        $currencyMatch = strtoupper($data['currency'] ?? '') === strtoupper($transaction->currency);

        $verified = $isSuccessful && $amountMatch && $currencyMatch;

        if (! $verified) {
            $this->logGateway('warning', 'Transaction verification mismatch', [
                'reference' => $transaction->provider_reference,
                'fw_status' => $fwStatus,
                'fw_amount' => $data['amount'] ?? null,
                'expected_amount' => $transaction->amount,
                'fw_currency' => $data['currency'] ?? null,
                'expected_currency' => $transaction->currency,
            ]);
        }

        return [
            'verified' => $verified,
            'status' => $verified ? 'paid' : 'failed',
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'gateway_reference' => $data['id'] ?? null,
            'flw_status' => $fwStatus,
            'message' => $verified
                ? 'Payment verified successfully.'
                : "Verification failed: status={$fwStatus}, amount_ok={$amountMatch}, currency_ok={$currencyMatch}",
        ];
    }

    public function processWebhook(Request $request, PaymentSetting $setting): ?array
    {
        if (! $this->verifyWebhookSignature($request, $setting)) {
            $this->logGateway('warning', 'Webhook signature verification failed');

            return null;
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        $this->logGateway('info', "Webhook event received: {$event}", [
            'tx_ref' => $data['tx_ref'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        if ($event !== 'charge.completed') {
            return null;
        }

        $status = strtolower($data['status'] ?? '');
        $mappedStatus = in_array($status, ['successful', 'completed'], true) ? 'paid' : 'failed';

        return [
            'provider_reference' => $data['tx_ref'] ?? null,
            'gateway_reference' => $data['id'] ?? null,
            'status' => $mappedStatus,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'raw' => $payload,
        ];
    }

    public function getWebhookSignature(Request $request, PaymentSetting $setting): ?string
    {
        return $request->header('verifhash') ?? $request->header('X-Flutterwave-Signature');
    }

    public function testConnection(): void
    {
        $this->ensureConfiguredForCheckout();

        $response = $this->apiGet('/banks/NG');
        $body = $response->json();

        if (! $response->successful() || ($body['status'] ?? '') !== 'success') {
            $message = $body['message'] ?? 'Flutterwave rejected the configured credentials.';
            abort(422, $message);
        }
    }

    private function verifyWebhookSignature(Request $request, PaymentSetting $setting): bool
    {
        $secret = $setting->webhook_secret;
        if (empty($secret)) {
            $this->logGateway('warning', 'No webhook secret configured');

            return false;
        }

        $receivedHash = $request->header('verifhash') ?? $request->header('X-Flutterwave-Signature');
        if (empty($receivedHash)) {
            $this->logGateway('warning', 'No verifhash header in webhook request');

            return false;
        }

        $matchesDashboardHash = hash_equals((string) $secret, (string) $receivedHash);
        $matchesLegacyComputedHash = hash_equals(hash('sha512', $request->getContent().$secret), (string) $receivedHash);

        if (! $matchesDashboardHash && ! $matchesLegacyComputedHash) {
            $this->logGateway('warning', 'Webhook hash mismatch', [
                'received' => substr($receivedHash, 0, 16).'...',
            ]);

            return false;
        }

        return true;
    }

    private function apiPost(string $endpoint, array $payload): Response
    {
        $this->ensureSecretKeyConfigured();

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->setting->secret_key,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->getApiBase().$endpoint, $payload);
    }

    private function apiGet(string $endpoint, array $query = []): Response
    {
        $this->ensureSecretKeyConfigured();

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->setting->secret_key,
        ])->timeout(30)->get($this->getApiBase().$endpoint, $query);
    }

    private function ensureConfiguredForCheckout(): void
    {
        if (empty($this->setting->public_key)) {
            abort(422, 'Flutterwave Public Key is required for the selected environment.');
        }

        $this->ensureSecretKeyConfigured();

        if ($this->setting->mode === 'live' && str_contains($this->setting->secret_key, '_TEST-')) {
            abort(422, 'Live Mode cannot use a Flutterwave test Secret Key.');
        }

        if ($this->setting->mode === 'live' && str_contains($this->setting->public_key, '_TEST-')) {
            abort(422, 'Live Mode cannot use a Flutterwave test Public Key.');
        }
    }

    private function ensureSecretKeyConfigured(): void
    {
        if (empty($this->setting->secret_key)) {
            abort(422, 'Flutterwave Secret Key is required for the selected environment.');
        }
    }

    private function resolveCustomerEmail(PaymentRequest $paymentRequest): string
    {
        $shipment = $paymentRequest->shipment;
        if ($shipment && is_array($shipment->metadata) && ! empty($shipment->metadata['receiver']['email'])) {
            return $shipment->metadata['receiver']['email'];
        }

        if ($shipment && ! empty($shipment->recipient_email)) {
            return $shipment->recipient_email;
        }

        return config('services.flutterwave.guest_email');
    }
}
