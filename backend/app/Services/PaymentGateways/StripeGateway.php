<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGateway extends BaseGateway
{
    protected string $name = 'stripe';

    protected string $displayName = 'Stripe';

    public function getLogoUrl(): string
    {
        return 'https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg';
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'NGN', 'ZAR', 'SGD', 'INR', 'AED', 'BRL'];
    }

    public function getFeePercentage(): float
    {
        return 2.9;
    }

    public function getFixedFee(): float
    {
        return 0.30;
    }

    public function initialize(PaymentSetting $setting): void
    {
        parent::initialize($setting);
        Stripe::setApiKey($this->setting->secret_key);
    }

    public function createCheckout(PaymentRequest $request, PaymentTransaction $transaction, Request $httpRequest): array
    {
        $amountInCents = (int) round((float) $transaction->amount * 100);

        $params = [
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($transaction->currency),
                        'unit_amount' => $amountInCents,
                        'product_data' => [
                            'name' => $request->title,
                            'description' => $request->reason,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('payment.callback', [
                'token' => $request->secure_token,
                'provider' => 'stripe',
                'session_id' => '{CHECKOUT_SESSION_ID}',
            ]),
            'cancel_url' => route('payment.cancel', ['token' => $request->secure_token]),
            'client_reference_id' => $transaction->provider_reference,
            'metadata' => [
                'payment_request_id' => $request->id,
                'payment_transaction_id' => $transaction->id,
                'tracking_number' => $request->shipment->tracking_number ?? '',
                'reference' => $transaction->provider_reference,
            ],
        ];

        try {
            $session = StripeSession::create($params);

            $transaction->update([
                'status' => 'pending',
                'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                    'stripe_session_id' => $session->id,
                    'stripe_payment_intent' => $session->payment_intent,
                ]),
            ]);

            return [
                'redirect_url' => $session->url,
                'provider_reference' => $transaction->provider_reference,
            ];
        } catch (ApiErrorException $e) {
            $this->logGateway('error', 'Stripe checkout failed: '.$e->getMessage());
            abort(422, 'Failed to initialize Stripe checkout. Please try again.');
        }
    }

    public function verifyTransaction(PaymentTransaction $transaction): array
    {
        $sessionId = $transaction->provider_payload['stripe_session_id'] ?? null;
        if (! $sessionId) {
            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'No Stripe session ID found.',
            ];
        }

        try {
            $session = StripeSession::retrieve([
                'id' => $sessionId,
                'expand' => ['payment_intent'],
            ]);

            $pi = $session->payment_intent;
            $paymentStatus = $session->payment_status;

            $verified = $paymentStatus === 'paid'
                && abs((float) $pi->amount / 100 - (float) $transaction->amount) < 0.01
                && strtolower($pi->currency) === strtolower($transaction->currency);

            return [
                'verified' => $verified,
                'status' => $verified ? 'paid' : 'failed',
                'amount' => ($pi->amount ?? 0) / 100,
                'currency' => strtoupper($pi->currency ?? ''),
                'gateway_reference' => $pi->id ?? null,
                'message' => $verified ? 'Payment verified successfully.' : 'Payment not completed.',
            ];
        } catch (\Exception $e) {
            $this->logGateway('error', 'Stripe verification failed: '.$e->getMessage());

            return [
                'verified' => false,
                'status' => 'failed',
                'message' => 'Verification error: '.$e->getMessage(),
            ];
        }
    }

    public function processWebhook(Request $request, PaymentSetting $setting): ?array
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (empty($sigHeader)) {
            return null;
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $setting->webhook_secret ?: $setting->secret_key
            );
        } catch (SignatureVerificationException $e) {
            $this->logGateway('warning', 'Stripe webhook signature invalid: '.$e->getMessage());

            return null;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $reference = $session->metadata->reference ?? $session->client_reference_id ?? null;

            return [
                'provider_reference' => $reference,
                'gateway_reference' => $session->payment_intent ?? null,
                'status' => 'paid',
                'amount' => ($session->amount_total ?? 0) / 100,
                'currency' => strtoupper($session->currency ?? ''),
                'raw' => (array) $event,
            ];
        }

        return null;
    }

    public function getWebhookSignature(Request $request, PaymentSetting $setting): ?string
    {
        return $request->header('Stripe-Signature');
    }

    public function supportsRefund(): bool
    {
        return true;
    }
}
