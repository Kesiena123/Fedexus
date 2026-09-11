<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CryptoGateway extends BaseGateway
{
    protected string $name = 'crypto';

    protected string $displayName = 'Cryptocurrency';

    public function getLogoUrl(): string
    {
        return 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1c/Bitcoin_logo.svg/1200px-Bitcoin_logo.svg.png';
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'LTC', 'DOGE', 'SOL'];
    }

    public function getFeePercentage(): float
    {
        return 1.0;
    }

    public function getFixedFee(): float
    {
        return 0.0;
    }

    public function getCheckoutFields(): array
    {
        return [];
    }

    public function createCheckout(PaymentRequest $request, PaymentTransaction $transaction, Request $httpRequest): array
    {
        $config = $this->setting->config ?? [];
        $provider = $config['provider'] ?? 'nowpayments';

        if ($provider === 'nowpayments') {
            return $this->createNowPaymentsInvoice($request, $transaction);
        }

        return $this->createGenericCryptoInvoice($request, $transaction);
    }

    private function createNowPaymentsInvoice(PaymentRequest $request, PaymentTransaction $transaction): array
    {
        $config = $this->setting->config ?? [];
        $apiKey = $this->setting->api_key;

        $payload = [
            'price_amount' => (float) $transaction->amount,
            'price_currency' => strtolower($transaction->currency),
            'pay_currency' => strtolower($config['preferred_coin'] ?? 'btc'),
            'order_id' => $transaction->provider_reference,
            'order_description' => $request->title.' - '.$request->reason,
            'ipn_callback_url' => route('api.payments.webhook', ['provider' => 'crypto']),
            'success_url' => route('payment.callback', [
                'token' => $request->secure_token,
                'provider' => 'crypto',
            ]),
            'cancel_url' => route('payment.cancel', ['token' => $request->secure_token]),
        ];

        $baseUrl = config('services.nowpayments.base_url');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post(rtrim($baseUrl, '/').'/invoice', $payload);

        $body = $response->json();

        if (! $response->successful() || ! isset($body['invoice_url'])) {
            $this->logGateway('error', 'NOWPayments invoice creation failed', ['response' => $body]);
            abort(422, 'Failed to create crypto payment invoice. Please try again.');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                'invoice_id' => $body['id'] ?? null,
                'invoice_url' => $body['invoice_url'] ?? null,
                'pay_address' => $body['pay_address'] ?? null,
                'pay_amount' => $body['pay_amount'] ?? null,
                'pay_currency' => $body['pay_currency'] ?? null,
                'provider' => 'nowpayments',
            ]),
        ]);

        return [
            'redirect_url' => $body['invoice_url'],
            'provider_reference' => $transaction->provider_reference,
        ];
    }

    private function createGenericCryptoInvoice(PaymentRequest $request, PaymentTransaction $transaction): array
    {
        $config = $this->setting->config ?? [];
        $apiUrl = $config['api_url'] ?? '';
        $apiKey = $this->setting->api_key;

        if (empty($apiUrl)) {
            abort(422, 'Crypto payment provider API URL is not configured.');
        }

        $payload = [
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'reference' => $transaction->provider_reference,
            'description' => $request->title,
            'callback_url' => route('api.payments.webhook', ['provider' => 'crypto']),
            'return_url' => route('payment.callback', [
                'token' => $request->secure_token,
                'provider' => 'crypto',
            ]),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->post(rtrim($apiUrl, '/').'/create_invoice', $payload);

        $body = $response->json();

        if (! $response->successful() || ! isset($body['invoice_url'])) {
            $this->logGateway('error', 'Crypto invoice creation failed', ['response' => $body]);
            abort(422, 'Failed to create crypto payment invoice. Please try again.');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                'invoice_id' => $body['id'] ?? null,
                'invoice_url' => $body['invoice_url'] ?? null,
                'provider' => 'generic',
            ]),
        ]);

        return [
            'redirect_url' => $body['invoice_url'],
            'provider_reference' => $transaction->provider_reference,
        ];
    }

    public function verifyTransaction(PaymentTransaction $transaction): array
    {
        $provider = $transaction->provider_payload['provider'] ?? 'nowpayments';

        if ($provider === 'nowpayments') {
            return $this->verifyNowPayments($transaction);
        }

        return [
            'verified' => false,
            'status' => 'pending',
            'message' => 'Crypto verification pending webhook confirmation.',
        ];
    }

    private function verifyNowPayments(PaymentTransaction $transaction): array
    {
        $invoiceId = $transaction->provider_payload['invoice_id'] ?? null;
        if (! $invoiceId) {
            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'No NOWPayments invoice ID found.',
            ];
        }

        $baseUrl = config('services.nowpayments.base_url');

        $response = Http::withHeaders([
            'x-api-key' => $this->setting->api_key,
        ])->get(rtrim($baseUrl, '/')."/invoice/{$invoiceId}");

        $body = $response->json();

        if (! $response->successful()) {
            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'Failed to verify crypto payment.',
            ];
        }

        $status = $body['status'] ?? '';
        $paid = in_array($status, ['finished', 'confirmed', 'partially_paid']);

        return [
            'verified' => $paid,
            'status' => $paid ? 'paid' : 'pending',
            'amount' => $body['pay_amount'] ?? null,
            'currency' => strtoupper($body['pay_currency'] ?? ''),
            'gateway_reference' => $body['payment_id'] ?? null,
            'message' => $paid ? 'Crypto payment confirmed.' : 'Awaiting crypto confirmation.',
        ];
    }

    public function processWebhook(Request $request, PaymentSetting $setting): ?array
    {
        $payload = $request->json()->all();

        $ipnSecret = $setting->webhook_secret ?? ($setting->config['ipn_secret'] ?? '');
        if (! empty($ipnSecret)) {
            $signature = $request->header('x-nowpayments-sig');
            if ($signature) {
                $bodyForSign = $request->getContent();
                $expected = hash_hmac('sha512', $bodyForSign, $ipnSecret);
                if (! hash_equals($expected, $signature)) {
                    $this->logGateway('warning', 'NOWPayments webhook signature invalid');

                    return null;
                }
            }
        }

        $status = $payload['payment_status'] ?? '';
        if (in_array($status, ['finished', 'confirmed'])) {
            return [
                'provider_reference' => $payload['order_id'] ?? null,
                'gateway_reference' => $payload['payment_id'] ?? null,
                'status' => 'paid',
                'amount' => $payload['pay_amount'] ?? null,
                'currency' => strtoupper($payload['pay_currency'] ?? ''),
                'raw' => $payload,
            ];
        }

        return null;
    }

    public function getWebhookSignature(Request $request, PaymentSetting $setting): ?string
    {
        return $request->header('x-nowpayments-sig');
    }
}
