<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PaymentProof;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\NotificationDispatchService;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentSecurityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentRequestController extends Controller
{
    public function show(string $token, PaymentGatewayManager $gatewayManager)
    {
        $paymentRequest = PaymentRequest::with(['shipment.trackingEvents', 'transactions'])
            ->where('secure_token', $token)
            ->firstOrFail();

        $allowedMethods = $paymentRequest->metadata['allowed_methods'] ?? ($paymentRequest->requested_method ? [$paymentRequest->requested_method] : null);
        $enabledGateways = $gatewayManager->getActiveGateways();

        if ($allowedMethods) {
            $enabledGateways = $enabledGateways->filter(fn ($g) => in_array($g['name'], $allowedMethods))->values();
        }

        $bankTransferDetails = null;
        if ($enabledGateways->pluck('name')->contains('bank_transfer')) {
            $btSetting = PaymentSetting::where('gateway_name', 'bank_transfer')->where('is_active', true)->first();
            if ($btSetting) {
                $btGateway = $gatewayManager->get('bank_transfer');
                if ($btGateway) {
                    $bankTransferDetails = $btGateway->getCheckoutFields();
                }
            }
        }

        $transactions = $paymentRequest->transactions()->latest()->get();

        return view('pages.payment-request', compact('paymentRequest', 'enabledGateways', 'bankTransferDetails', 'transactions'));
    }

    public function start(Request $request, string $token, PaymentGatewayManager $gatewayManager, PaymentSecurityService $security)
    {
        $paymentRequest = PaymentRequest::with('shipment')
            ->where('secure_token', $token)
            ->firstOrFail();

        abort_unless(in_array($paymentRequest->status, ['payment_required', 'failed']), 422, 'This payment request is not accepting payments.');
        abort_if($paymentRequest->due_at && $paymentRequest->due_at->isPast(), 422, 'This payment request has expired.');

        $data = $request->validate([
            'provider' => ['required', 'string', 'max:80'],
        ]);

        $gatewayName = $data['provider'];
        $security->isGatewayEnabled($gatewayName) ?: abort(422, 'The selected payment gateway is not enabled.');

        $gateway = $gatewayManager->getForCheckout($gatewayName);

        $allowedMethods = $paymentRequest->metadata['allowed_methods'] ?? ($paymentRequest->requested_method ? [$paymentRequest->requested_method] : null);
        if ($allowedMethods && ! in_array($gatewayName, $allowedMethods)) {
            abort(403, 'This payment request is restricted to specific payment methods.');
        }

        $transaction = DB::transaction(function () use ($paymentRequest, $gateway, $gatewayName, $security): PaymentTransaction {
            $paymentRequest->update(['status' => 'payment_initiated']);

            $fees = $security->calculateFee(
                (float) $paymentRequest->amount,
                $gateway->getFeePercentage(),
                $gateway->getFixedFee()
            );

            return PaymentTransaction::create([
                'payment_request_id' => $paymentRequest->id,
                'provider' => $gatewayName,
                'provider_reference' => strtoupper($gatewayName).'-'.strtoupper(Str::random(18)),
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
                'fee_amount' => $fees['fee_amount'],
                'net_amount' => $fees['net_amount'],
                'status' => 'initiated',
                'provider_payload' => [
                    'mode' => $gateway->getSetting()->mode ?? 'test',
                    'fee_percentage' => $gateway->getFeePercentage(),
                    'fixed_fee' => $gateway->getFixedFee(),
                ],
            ]);
        });

        $security->logPaymentAudit(
            'payment.initiated',
            $transaction,
            null,
            'initiated',
            $request,
            null,
            ['provider' => $gatewayName, 'amount' => $paymentRequest->amount, 'currency' => $paymentRequest->currency]
        );

        try {
            $checkoutResult = $gateway->createCheckout($paymentRequest, $transaction, $request);
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            $paymentRequest->update(['status' => 'payment_required']);

            $security->logPaymentAudit('payment.checkout_failed', $transaction, 'initiated', 'failed', $request, null, ['error' => $e->getMessage()]);
            Log::error('[PaymentRequestController] Checkout failed', [
                'payment_request_id' => $paymentRequest->id,
                'provider' => $gatewayName,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to initialize payment. Please try a different method or contact support.'], 422);
            }

            return back()->with('error', 'Failed to initialize payment. Please try a different method or contact support.');
        }

        $redirectUrl = $checkoutResult['redirect_url'] ?? '';
        $instructions = $checkoutResult['instructions'] ?? null;
        $showInstructions = $checkoutResult['show_instructions'] ?? false;
        $inlineConfig = $checkoutResult['inline_config'] ?? null;

        Log::info('[PaymentRequestController] Checkout result', [
            'payment_request_id' => $paymentRequest->id,
            'provider' => $gatewayName,
            'has_redirect_url' => ! empty($redirectUrl),
            'redirect_url' => $redirectUrl,
            'show_instructions' => $showInstructions,
            'transaction_reference' => $transaction->provider_reference,
        ]);

        if ($redirectUrl) {
            $security->logPaymentAudit('payment.redirecting', $transaction, 'initiated', 'pending', $request, null, [
                'redirect_url' => $redirectUrl,
            ]);

            if ($request->expectsJson()) {
                $payload = ['redirect_url' => $redirectUrl];
                if ($inlineConfig) {
                    $payload['inline_config'] = $inlineConfig;
                }

                return response()->json($payload);
            }

            return redirect($redirectUrl);
        }

        if ($showInstructions) {
            $bankAccounts = BankAccount::active()->ordered()->get();
            $reference = $transaction->provider_reference;

            if ($request->expectsJson()) {
                return response()->json([
                    'redirect_url' => route('payment-request.instructions', $paymentRequest->secure_token),
                    'show_instructions' => true,
                    'instructions' => $instructions,
                    'payment_request_token' => $paymentRequest->secure_token,
                    'bank_accounts' => $bankAccounts->toArray(),
                    'reference' => $reference,
                ]);
            }

            return view('pages.bank-transfer-instructions', [
                'paymentRequest' => $paymentRequest,
                'transaction' => $transaction,
                'instructions' => $instructions,
                'bankAccounts' => $bankAccounts,
                'reference' => $reference,
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Payment session created.', 'reference' => $transaction->provider_reference]);
        }

        return back()->with('success', 'Payment session created. Reference: '.$transaction->provider_reference)
            ->with('transaction_reference', $transaction->provider_reference);
    }

    public function instructions(string $token)
    {
        $paymentRequest = PaymentRequest::with('shipment')
            ->where('secure_token', $token)
            ->firstOrFail();

        $transaction = PaymentTransaction::where('payment_request_id', $paymentRequest->id)
            ->where('provider', 'bank_transfer')
            ->latest()
            ->first();

        if (!$transaction) {
            return redirect()->route('payment-request.show', $token)
                ->with('error', 'No active bank transfer session found. Please try again.');
        }

        $bankAccounts = BankAccount::active()->ordered()->get();
        $reference = $transaction->provider_reference;

        return view('pages.bank-transfer-instructions', [
            'paymentRequest' => $paymentRequest,
            'transaction' => $transaction,
            'instructions' => [],
            'bankAccounts' => $bankAccounts,
            'reference' => $reference,
            'amount' => $paymentRequest->amount,
            'currency' => $paymentRequest->currency,
        ]);
    }

    public function callback(Request $request, string $token, string $provider, PaymentGatewayManager $gatewayManager, PaymentSecurityService $security)
    {
        Log::info('[PaymentRequestController] Callback received', [
            'provider' => $provider,
            'token' => $token,
            'query' => $request->query(),
            'method' => $request->method(),
        ]);

        $paymentRequest = PaymentRequest::with('shipment')
            ->where('secure_token', $token)
            ->firstOrFail();

        $transaction = $paymentRequest->transactions()
            ->where('provider', $provider)
            ->latest()
            ->first();

        if (! $transaction) {
            return view('pages.payment-cancel', [
                'paymentRequest' => $paymentRequest,
                'message' => 'No payment transaction found.',
            ]);
        }

        if (in_array($transaction->status, ['verified', 'paid'])) {
            return view('pages.payment-success', [
                'paymentRequest' => $paymentRequest,
                'transaction' => $transaction,
                'provider' => $provider,
                'message' => 'Payment has already been verified.',
            ]);
        }

        if ($provider === 'flutterwave') {
            $fwTxId = $request->query('transaction_id');
            $fwStatus = $request->query('status');
            $fwTxRef = $request->query('tx_ref');

            if ($fwTxId && ! $transaction->provider_payload['flutterwave_tx_id'] ?? null) {
                $transaction->update([
                    'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                        'flutterwave_tx_id' => $fwTxId,
                        'flutterwave_redirect_status' => $fwStatus,
                    ]),
                ]);
            }
        }

        if (in_array($provider, ['stripe', 'paypal', 'flutterwave', 'crypto'])) {
            try {
                $gateway = $gatewayManager->get($provider);
                if ($gateway) {
                    $result = $gateway->verifyTransaction($transaction);

                    if ($result['verified']) {
                        DB::transaction(function () use ($transaction, $paymentRequest, $security, $result, $request) {
                            $transaction->update([
                                'status' => 'verified',
                                'verified_at' => now(),
                                'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                                    'verification' => $result,
                                ]),
                            ]);

                            $paymentRequest->update([
                                'status' => 'paid',
                                'paid_at' => now(),
                                'verified_at' => now(),
                            ]);

                            $security->markWebhookProcessed($transaction->provider_reference, $transaction->provider);
                            $security->logPaymentAudit('payment.verified', $transaction, 'payment_required', 'paid', $request);
                        });

                        Log::info('[PaymentRequestController] Payment verified', [
                            'provider' => $provider,
                            'transaction_id' => $transaction->id,
                            'reference' => $transaction->provider_reference,
                        ]);

                        return view('pages.payment-success', [
                            'paymentRequest' => $paymentRequest,
                            'transaction' => $transaction->fresh(),
                            'provider' => $provider,
                            'message' => 'Payment verified successfully!',
                        ]);
                    } else {
                        $transaction->update([
                            'status' => 'failed',
                            'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                                'verification' => $result,
                            ]),
                        ]);
                        $paymentRequest->update(['status' => 'payment_required']);

        Log::warning('[PaymentRequestController] Payment verification failed', [
                            'provider' => $provider,
                            'transaction_id' => $transaction->id,
                            'reference' => $transaction->provider_reference,
                            'message' => $result['message'],
                        ]);

                        return view('pages.payment-cancel', [
                            'paymentRequest' => $paymentRequest,
                            'message' => 'Payment verification failed. '.$result['message'],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("[PaymentCallback] Verification error for {$provider}", [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id,
                ]);
                $security->logPaymentAudit('payment.verification_error', $transaction, null, null, $request, null, ['error' => $e->getMessage()]);
            }
        }

        return view('pages.payment-success', [
            'paymentRequest' => $paymentRequest,
            'transaction' => $transaction,
            'provider' => $provider,
            'message' => 'Your payment is being processed. You will be notified once verified.',
        ]);
    }

    public function success(Request $request, string $token)
    {
        $paymentRequest = PaymentRequest::with(['shipment', 'transactions'])
            ->where('secure_token', $token)
            ->firstOrFail();

        $transaction = $paymentRequest->transactions()->latest()->first();

        return view('pages.payment-success', [
            'paymentRequest' => $paymentRequest,
            'transaction' => $transaction,
            'provider' => $transaction?->provider ?? '',
            'message' => 'Thank you for your payment!',
        ]);
    }

    public function cancel(Request $request, string $token)
    {
        $paymentRequest = PaymentRequest::with('shipment')
            ->where('secure_token', $token)
            ->firstOrFail();

        if (in_array($paymentRequest->status, ['payment_initiated', 'awaiting_verification'])) {
            $paymentRequest->update(['status' => 'payment_required']);
        }

        return view('pages.payment-cancel', compact('paymentRequest'));
    }

    public function uploadProof(Request $request, string $token)
    {
        $paymentRequest = PaymentRequest::with(['shipment', 'transactions'])
            ->where('secure_token', $token)
            ->firstOrFail();

        abort_unless(in_array($paymentRequest->status, ['payment_required', 'payment_initiated', 'awaiting_verification']), 422, 'This payment request is not accepting uploads.');

        $data = $request->validate([
            'proof_file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,pdf',
            ],
            'payment_reference' => ['required', 'string', 'max:100'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = $paymentRequest->transactions()
            ->where('provider', 'bank_transfer')
            ->latest()
            ->first();

        $file = $request->file('proof_file');
        $fileHash = hash_file('sha256', $file->getRealPath());

        $existingHash = PaymentProof::where('file_hash', $fileHash)->first();
        if ($existingHash) {
            return back()->withErrors(['proof_file' => 'This file has already been uploaded for a different payment request.']);
        }

        $filename = 'proofs/'.$paymentRequest->id.'_'.Str::random(16).'.'.$file->getClientOriginalExtension();
        $file->storeAs('private', $filename);

        $proof = PaymentProof::create([
            'payment_request_id' => $paymentRequest->id,
            'payment_transaction_id' => $transaction?->id,
            'tracking_number' => $paymentRequest->shipment->tracking_number ?? '',
            'payment_reference' => $data['payment_reference'],
            'file_path' => $filename,
            'file_hash' => $fileHash,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        if ($transaction) {
            $transaction->update([
                'status' => 'pending',
                'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                    'proof_uploaded' => true,
                    'proof_id' => $proof->id,
                    'proof_uploaded_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        $paymentRequest->update(['status' => 'awaiting_verification']);

        Log::info('[PaymentRequest] Proof of payment uploaded', [
            'payment_request_id' => $paymentRequest->id,
            'proof_id' => $proof->id,
            'tracking_number' => $paymentRequest->shipment->tracking_number ?? '',
            'payment_reference' => $data['payment_reference'],
            'file_size' => $file->getSize(),
        ]);

        try {
            $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->orWhere('is_admin', true)->get();
            $notifier = app(NotificationDispatchService::class);
            foreach ($admins as $admin) {
                $notifier->send($admin, 'New Bank Transfer Proof Uploaded', "A bank transfer payment proof has been submitted for {$paymentRequest->title} (ref: {$data['payment_reference']}). Review required.", [
                    'type' => 'payment',
                    'payment_request_id' => $paymentRequest->id,
                    'proof_id' => $proof->id,
                    'tracking_number' => $paymentRequest->shipment->tracking_number ?? '',
                    'payment_reference' => $data['payment_reference'],
                    'event' => 'bank_transfer.proof_uploaded',
                ], ['in_app']);
            }
        } catch (\Throwable $e) {
            Log::warning('[PaymentRequest] Failed to notify admins of proof upload', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Payment proof uploaded successfully! Your payment is now awaiting admin verification.');
    }

    public function instructionsPdf(string $token)
    {
        $paymentRequest = PaymentRequest::with('shipment')
            ->where('secure_token', $token)
            ->firstOrFail();

        $bankAccounts = BankAccount::active()->ordered()->get();
        $transaction = $paymentRequest->transactions()
            ->where('provider', 'bank_transfer')
            ->latest()
            ->first();

        $reference = $transaction?->provider_reference ?? $paymentRequest->payment_reference ?? 'N/A';
        $amount = $paymentRequest->amount;
        $currency = $paymentRequest->currency;
        $trackingNumber = $paymentRequest->shipment?->tracking_number ?? 'N/A';

        $settings = app(\App\Support\AppSettings::class);
        $companyName = $settings->companyName();
        $companyLogo = $settings->logo();

        $pdf = Pdf::loadView('pages.instructions-pdf', compact(
            'paymentRequest', 'bankAccounts', 'reference', 'amount', 'currency',
            'trackingNumber', 'companyName', 'companyLogo'
        ));
        $pdf->setPaper('a4');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download("bank-transfer-instructions-{$paymentRequest->secure_token}.pdf");
    }
}
