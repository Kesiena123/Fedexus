<?php

namespace App\Services\PaymentGateways;

use App\Models\BankAccount;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BankTransferGateway extends BaseGateway
{
    protected string $name = 'bank_transfer';

    protected string $displayName = 'Bank Transfer';

    public function getLogoUrl(): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="12" fill="#0d5368"/><text x="50" y="58" text-anchor="middle" fill="white" font-family="Arial" font-size="14" font-weight="bold">BANK</text></svg>');
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'NGN', 'GHS', 'KES', 'ZAR', 'CAD'];
    }

    public function getFeePercentage(): float
    {
        return 0.0;
    }

    public function getFixedFee(): float
    {
        return 0.0;
    }

    public function getCheckoutFields(): array
    {
        return [
            [
                'name' => 'bank_name',
                'label' => 'Bank Name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'account_number',
                'label' => 'Account Number',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'proof_of_payment',
                'label' => 'Proof of Payment',
                'type' => 'file',
                'required' => true,
                'accept' => '.jpg,.jpeg,.png,.pdf',
                'max_size' => 10240,
            ],
        ];
    }

    public function createCheckout(PaymentRequest $paymentRequest, PaymentTransaction $transaction, Request $httpRequest): array
    {
        $paymentReference = $this->generatePaymentReference($paymentRequest);

        $paymentRequest->update(['payment_reference' => $paymentReference]);

        $bankAccount = $this->resolveBankAccount($paymentRequest);

        $transaction->update([
            'provider_reference' => $paymentReference,
            'status' => 'pending',
            'provider_payload' => array_merge($transaction->provider_payload ?? [], [
                'bank_account_id' => $bankAccount?->id,
                'bank_name' => $bankAccount?->bank_name ?? '',
                'account_name' => $bankAccount?->account_name ?? '',
                'account_number' => $bankAccount?->account_number ?? '',
                'swift_bic' => $bankAccount?->swift_bic ?? '',
                'iban' => $bankAccount?->iban ?? '',
                'routing_number' => $bankAccount?->routing_number ?? '',
                'payment_instructions' => $bankAccount?->payment_instructions ?? '',
                'mode' => 'manual',
                'note' => 'Manual bank transfer. Awaiting proof of payment and admin verification.',
            ]),
        ]);

        return [
            'redirect_url' => '',
            'provider_reference' => $paymentReference,
            'show_instructions' => true,
            'instructions' => $this->getPaymentInstructions($transaction, $paymentRequest),
        ];
    }

    public function verifyTransaction(PaymentTransaction $transaction): array
    {
        return [
            'verified' => false,
            'status' => 'pending',
            'message' => 'Bank transfers require manual admin verification.',
        ];
    }

    public function processWebhook(Request $request, PaymentSetting $setting): ?array
    {
        return null;
    }

    public function getWebhookSignature(Request $request, PaymentSetting $setting): ?string
    {
        return null;
    }

    public function getPaymentInstructions(PaymentTransaction $transaction, ?PaymentRequest $paymentRequest = null): array
    {
        $bankAccount = $this->resolveBankAccount($paymentRequest);

        $paymentReference = $transaction->provider_reference;

        return [
            'bank_name' => $bankAccount?->bank_name ?? '',
            'account_name' => $bankAccount?->account_name ?? '',
            'account_number' => $bankAccount?->account_number ?? '',
            'routing_number' => $bankAccount?->routing_number ?? '',
            'swift_bic' => $bankAccount?->swift_bic ?? '',
            'iban' => $bankAccount?->iban ?? '',
            'branch_name' => $bankAccount?->branch_name ?? '',
            'branch_address' => $bankAccount?->branch_address ?? '',
            'bank_logo' => $bankAccount?->bank_logo ?? '',
            'amount' => number_format((float) $transaction->amount, 2),
            'currency' => $transaction->currency,
            'reference' => $paymentReference,
            'instructions' => $bankAccount?->payment_instructions ?? 'Please include your payment reference in the transfer description.',
            'bank_account_id' => $bankAccount?->id,
        ];
    }

    public function getActiveBankAccounts(?string $currency = null): \Illuminate\Support\Collection
    {
        $query = BankAccount::active()->ordered();
        if ($currency) {
            $query->where('supported_currency', $currency);
        }
        return $query->get();
    }

    private function generatePaymentReference(PaymentRequest $paymentRequest): string
    {
        $trackingNumber = $paymentRequest->shipment->tracking_number ?? 'PAY';
        $shortTracking = substr(str_replace('-', '', $trackingNumber), -6);
        $random = strtoupper(Str::random(4));
        $reference = 'PAY-'.$shortTracking.'-'.$random;

        $exists = PaymentTransaction::where('provider_reference', $reference)->exists()
            || PaymentRequest::where('payment_reference', $reference)->exists();

        if ($exists) {
            $reference = 'PAY-'.$shortTracking.'-'.strtoupper(Str::random(6));
        }

        return $reference;
    }

    private function resolveBankAccount(?PaymentRequest $paymentRequest): ?BankAccount
    {
        if (!$paymentRequest) {
            return BankAccount::active()->ordered()->first();
        }

        $account = BankAccount::active()
            ->where('supported_currency', $paymentRequest->currency)
            ->where('is_default', true)
            ->first();

        if (!$account) {
            $account = BankAccount::active()
                ->where('supported_currency', $paymentRequest->currency)
                ->ordered()
                ->first();
        }

        if (!$account) {
            $account = BankAccount::active()->where('is_default', true)->first();
        }

        if (!$account) {
            $account = BankAccount::active()->ordered()->first();
        }

        return $account;
    }
}
