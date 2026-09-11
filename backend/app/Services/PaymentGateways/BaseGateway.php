<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Log;

abstract class BaseGateway implements PaymentGatewayInterface
{
    protected PaymentSetting $setting;

    protected string $name;

    protected string $displayName;

    public function initialize(PaymentSetting $setting): void
    {
        $this->setting = $setting;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getSetting(): PaymentSetting
    {
        return $this->setting;
    }

    public function supportsRefund(): bool
    {
        return false;
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
        return [];
    }

    protected function logGateway(string $level, string $message, array $context = []): void
    {
        $context['gateway'] = $this->name;
        Log::$level("[PaymentGateway:{$this->name}] {$message}", $context);
    }

    protected function generateReference(string $prefix = ''): string
    {
        $prefix = $prefix ?: strtoupper($this->name);

        return $prefix.'-'.strtoupper(uniqid('', false)).'-'.strtoupper(substr(uniqid('', false), -6));
    }

    protected function ensureHttp(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return 'https://'.$url;
        }

        return $url;
    }
}
