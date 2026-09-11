<?php

namespace App\Contracts;

use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function initialize(PaymentSetting $setting): void;

    public function getName(): string;

    public function getDisplayName(): string;

    public function getLogoUrl(): string;

    public function getSupportedCurrencies(): array;

    public function supportsRefund(): bool;

    public function createCheckout(PaymentRequest $request, PaymentTransaction $transaction, Request $httpRequest): array;

    public function verifyTransaction(PaymentTransaction $transaction): array;

    public function processWebhook(Request $request, PaymentSetting $setting): ?array;

    public function getWebhookSignature(Request $request, PaymentSetting $setting): ?string;

    public function getCheckoutFields(): array;

    public function getFeePercentage(): float;

    public function getFixedFee(): float;
}
