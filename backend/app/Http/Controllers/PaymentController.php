<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use App\Services\AdminAuditLogger;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentSecurityService;
use App\Services\PaymentStageService;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Shipment $shipment): JsonResponse
    {
        $payments = $shipment->payments();

        if (request()->user()?->role === 'customer') {
            $payments->where('status', '!=', 'locked');
        }

        return response()->json(['payments' => $payments->orderBy('stage')->get()]);
    }

    public function checkout(Request $request, Payment $payment, PaymentGatewayManager $gatewayManager, PaymentSecurityService $security): JsonResponse
    {
        abort_unless(in_array($payment->status, ['pending', 'checkout_created'], true), 422, 'This payment stage is not available.');
        abort_if(in_array($payment->shipment->status, ['paused', 'cancelled', 'delivered'], true), 422, 'This shipment is not accepting new payments.');

        $data = $request->validate(['provider' => ['required', 'string', 'max:80']]);
        $gatewayName = $data['provider'];

        $security->isGatewayEnabled($gatewayName) ?: abort(422, 'The selected payment gateway is not enabled.');
        $gateway = $gatewayManager->getForCheckout($gatewayName);

        $reference = strtoupper($gatewayName).'-'.strtoupper(Str::random(16));

        $payment->update([
            'provider' => $gatewayName,
            'provider_reference' => $reference,
            'status' => 'checkout_created',
        ]);

        $security->logPaymentAudit(
            'payment_stage.checkout_created',
            null,
            'pending',
            'checkout_created',
            $request,
            $request->user()?->id,
            ['payment_id' => $payment->id, 'provider' => $gatewayName, 'reference' => $reference]
        );

        return response()->json([
            'payment' => $payment,
            'checkout' => [
                'provider' => $gatewayName,
                'reference' => $reference,
                'amount' => $payment->amount,
                'currency' => \App\Support\AppSettings::currency(),
                'redirect_url' => url('/checkout/'.$reference),
            ],
        ]);
    }

    public function webhook(string $provider, Request $request, PaymentGatewayManager $gatewayManager, PaymentSecurityService $security): JsonResponse
    {
        $gatewaySetting = PaymentSetting::where('gateway_name', $provider)->where('is_active', true)->first();
        if (! $gatewaySetting) {
            return response()->json(['error' => 'Gateway not found or inactive'], 404);
        }

        $gatewayAdapter = $gatewayManager->get($provider);
        if (! $gatewayAdapter) {
            return response()->json(['error' => 'Gateway adapter not found'], 404);
        }

        $webhookData = $gatewayAdapter->processWebhook($request, $gatewaySetting);
        if (! $webhookData) {
            return response()->json(['received' => true, 'type' => 'unhandled_event']);
        }

        $reference = $webhookData['provider_reference'] ?? null;
        if (empty($reference)) {
            return response()->json(['error' => 'Missing payment reference'], 422);
        }

        if ($security->isWebhookProcessed($reference, $provider)) {
            return response()->json(['received' => true, 'type' => 'duplicate', 'message' => 'Webhook already processed.']);
        }

        $payment = Payment::where('provider', $provider)->where('provider_reference', $reference)->first();
        if ($payment) {
            if ($webhookData['status'] === 'paid') {
                DB::transaction(function () use ($payment, $webhookData, $security, $request) {
                    $payment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    $security->logPaymentAudit('payment_stage.paid', null, 'checkout_created', 'paid', $request, null, [
                        'payment_id' => $payment->id,
                        'gateway_reference' => $webhookData['gateway_reference'] ?? null,
                    ]);
                });
            }

            return response()->json(['received' => true, 'type' => 'payment_stage']);
        }

        $transaction = PaymentTransaction::with('paymentRequest')
            ->where('provider', $provider)
            ->where('provider_reference', $reference)
            ->first();

        if (! $transaction) {
            $security->logPaymentAudit('webhook.unknown_reference', null, null, null, $request, null, ['provider' => $provider, 'reference' => $reference]);

            return response()->json(['error' => 'Transaction not found'], 404);
        }

        if (in_array($transaction->status, ['verified', 'paid'])) {
            return response()->json(['received' => true, 'type' => 'duplicate']);
        }

        DB::transaction(function () use ($transaction, $webhookData, $provider, $security, $request) {
            $previousStatus = $transaction->status;

            if ($webhookData['status'] === 'paid') {
                $transaction->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                    'webhook_received_at' => now(),
                    'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                        'webhook_data' => $webhookData['raw'] ?? null,
                        'gateway_reference' => $webhookData['gateway_reference'] ?? null,
                    ]),
                ]);

                $transaction->paymentRequest->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'verified_at' => now(),
                ]);
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'webhook_received_at' => now(),
                    'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                        'webhook_data' => $webhookData['raw'] ?? null,
                        'failure_reason' => $webhookData['message'] ?? 'Payment failed',
                    ]),
                ]);

                $transaction->paymentRequest->update(['status' => 'failed']);
            }

            $security->markWebhookProcessed($reference, $provider);
            $security->logPaymentAudit(
                $webhookData['status'] === 'paid' ? 'payment.verified' : 'payment.failed',
                $transaction,
                $previousStatus,
                $transaction->status,
                $request
            );
        });

        return response()->json(['received' => true, 'type' => 'payment_request']);
    }

    public function flutterwaveWebhook(Request $request, PaymentGatewayManager $gatewayManager, PaymentSecurityService $security): JsonResponse
    {
        return $this->webhook('flutterwave', $request, $gatewayManager, $security);
    }

    public function verify(Request $request, Payment $payment, PaymentStageService $stages, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::PAYMENTS_MANAGE);

        abort_unless($payment->status === 'paid', 422, 'Only paid stages can be verified.');

        $payment->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        $next = $stages->unlockNextStage($payment->shipment);

        $audit->log($request, $request->user(), 'payment.verified', [
            'target_type' => 'payment',
            'target_id' => $payment->id,
            'previous_values' => ['status' => 'paid'],
            'new_values' => ['status' => $payment->status, 'next_stage_id' => $next?->id],
        ]);

        return response()->json(['payment' => $payment->fresh(), 'next_stage' => $next]);
    }

    public function unlock(Request $request, Shipment $shipment, PaymentStageService $stages, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::PAYMENTS_MANAGE);

        $payment = $stages->unlockNextStage($shipment);

        abort_unless($payment, 422, 'No payment stage can be unlocked for this shipment.');

        $audit->log($request, $request->user(), 'payment.stage.unlocked', [
            'target_type' => 'payment',
            'target_id' => $payment->id,
            'new_values' => ['status' => $payment->status, 'shipment_id' => $shipment->id, 'stage' => $payment->stage],
        ]);

        return response()->json(['payment' => $payment]);
    }

    public function reject(Request $request, Payment $payment, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorizePermission($request, AdminPermissions::PAYMENTS_MANAGE);

        $payment->update(['status' => 'rejected']);

        $audit->log($request, $request->user(), 'payment.rejected', [
            'target_type' => 'payment',
            'target_id' => $payment->id,
            'previous_values' => ['status' => 'paid'],
            'new_values' => ['status' => $payment->status],
        ]);

        return response()->json(['payment' => $payment->fresh()]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        AdminPermissions::authorize($request->user(), $permission);
    }
}
