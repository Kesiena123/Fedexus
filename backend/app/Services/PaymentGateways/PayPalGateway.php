<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalGateway extends BaseGateway
{
    protected string $name = 'paypal';

    protected string $displayName = 'PayPal';

    private ?string $accessToken = null;

    public function getLogoUrl(): string
    {
        return 'https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg';
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'SGD', 'BRL', 'MXN'];
    }

    public function getFeePercentage(): float
    {
        return 3.49;
    }

    public function getFixedFee(): float
    {
        return 0.49;
    }

    private function getBaseUrl(): string
    {
        return $this->setting->mode === 'live'
            ? config('services.paypal.live_base_url')
            : config('services.paypal.sandbox_base_url');
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth(
            $this->setting->api_key,
            $this->setting->secret_key
        )->post("{$this->getBaseUrl()}/v1/oauth2/token", [
            'grant_type' => 'client_credentials',
        ]);

        abort_unless($response->successful(), 422, 'Failed to authenticate with PayPal.');
        $this->accessToken = $response->json('access_token');

        return $this->accessToken;
    }

    public function createCheckout(PaymentRequest $request, PaymentTransaction $transaction, Request $httpRequest): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $transaction->provider_reference,
                    'description' => $request->title.' - '.$request->reason,
                    'amount' => [
                        'currency_code' => $transaction->currency,
                        'value' => number_format((float) $transaction->amount, 2, '.', ''),
                    ],
                    'custom_id' => (string) $request->id,
                    'invoice_id' => $transaction->provider_reference,
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => $this->setting->merchant_name ?: config('app.name'),
                        'landing_page' => 'BILLING',
                        'user_action' => 'PAY_NOW',
                        'return_url' => route('payment.callback', [
                            'token' => $request->secure_token,
                            'provider' => 'paypal',
                        ]),
                        'cancel_url' => route('payment.cancel', ['token' => $request->secure_token]),
                    ],
                ],
            ],
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->getBaseUrl()}/v2/checkout/orders", $payload);

        $body = $response->json();

        if (! $response->successful()) {
            $this->logGateway('error', 'PayPal order creation failed', ['response' => $body]);
            abort(422, 'Failed to initialize PayPal checkout. Please try again.');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                'paypal_order_id' => $body['id'] ?? null,
            ]),
        ]);

        $approveUrl = collect($body['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? '';

        return [
            'redirect_url' => $approveUrl,
            'provider_reference' => $transaction->provider_reference,
        ];
    }

    public function verifyTransaction(PaymentTransaction $transaction): array
    {
        $orderId = $transaction->provider_payload['paypal_order_id'] ?? null;
        if (! $orderId) {
            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'No PayPal order ID found.',
            ];
        }

        $response = Http::withToken($this->getAccessToken())
            ->get("{$this->getBaseUrl()}/v2/checkout/orders/{$orderId}");

        $body = $response->json();

        if (! $response->successful()) {
            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'Failed to retrieve PayPal order.',
            ];
        }

        $status = $body['status'] ?? '';
        $amount = $body['purchase_units'][0]['amount']['value'] ?? '0';
        $currency = $body['purchase_units'][0]['amount']['currency_code'] ?? '';

        $verified = $status === 'COMPLETED'
            && abs((float) $amount - (float) $transaction->amount) < 0.01
            && strtoupper($currency) === strtoupper($transaction->currency);

        return [
            'verified' => $verified,
            'status' => $verified ? 'paid' : 'failed',
            'amount' => (float) $amount,
            'currency' => strtoupper($currency),
            'gateway_reference' => $body['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
            'message' => $verified ? 'Payment verified successfully.' : 'Payment not completed.',
        ];
    }

    public function processWebhook(Request $request, PaymentSetting $setting): ?array
    {
        $payload = $request->json()->all();
        $eventType = $payload['event_type'] ?? '';

        if (! in_array($eventType, ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED'], true)) {
            return null;
        }

        $resource = $payload['resource'] ?? [];
        $customId = $resource['custom_id'] ?? $resource['invoice_id'] ?? null;
        $captureId = $resource['id'] ?? null;

        return [
            'provider_reference' => $resource['custom_id'] ?? null,
            'gateway_reference' => $captureId,
            'status' => 'paid',
            'amount' => $resource['amount']['value'] ?? null,
            'currency' => $resource['amount']['currency_code'] ?? null,
            'raw' => $payload,
        ];
    }

    public function getWebhookSignature(Request $request, PaymentSetting $setting): ?string
    {
        return $request->header('PayPal-Transmission-Id')
            ?: $request->header('PayPal-Transmission-Sig');
    }

    public function supportsRefund(): bool
    {
        return true;
    }
}
