<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentSetting;
use App\Services\PaymentGateways\BankTransferGateway;
use App\Services\PaymentGateways\CryptoGateway;
use App\Services\PaymentGateways\FlutterwaveGateway;
use App\Services\PaymentGateways\PayPalGateway;
use App\Services\PaymentGateways\StripeGateway;
use Illuminate\Support\Collection;

class PaymentGatewayManager
{
    private array $gateways = [];

    private array $instances = [];

    public function __construct()
    {
        $this->gateways = [
            'flutterwave' => FlutterwaveGateway::class,
            'stripe' => StripeGateway::class,
            'paypal' => PayPalGateway::class,
            'bank_transfer' => BankTransferGateway::class,
            'crypto' => CryptoGateway::class,
        ];
    }

    public function get(string $name): ?PaymentGatewayInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (! isset($this->gateways[$name])) {
            return null;
        }

        $setting = PaymentSetting::where('gateway_name', $name)->where('is_active', true)->first();
        if (! $setting) {
            return null;
        }

        $gateway = new $this->gateways[$name];
        $gateway->initialize($setting);
        $this->instances[$name] = $gateway;

        return $gateway;
    }

    public function getForCheckout(string $name): PaymentGatewayInterface
    {
        $gateway = $this->get($name);
        abort_unless($gateway, 422, "Payment gateway '{$name}' is not available or not enabled.");

        return $gateway;
    }

    public function makeForSetting(PaymentSetting $setting): ?PaymentGatewayInterface
    {
        if (! isset($this->gateways[$setting->gateway_name])) {
            return null;
        }

        $gateway = new $this->gateways[$setting->gateway_name];
        $gateway->initialize($setting);

        return $gateway;
    }

    public function getActive(): Collection
    {
        return PaymentSetting::where('is_active', true)
            ->orderBy('gateway_name')
            ->get()
            ->filter(function (PaymentSetting $setting) {
                return isset($this->gateways[$setting->gateway_name]);
            })
            ->values();
    }

    public function getActiveGateways(): Collection
    {
        return $this->getActive()->map(function (PaymentSetting $setting) {
            $gateway = $this->get($setting->gateway_name);

            return $gateway ? [
                'name' => $gateway->getName(),
                'display_name' => $gateway->getDisplayName(),
                'logo_url' => $gateway->getLogoUrl(),
                'currencies' => $gateway->getSupportedCurrencies(),
                'fee_percentage' => $gateway->getFeePercentage(),
                'fixed_fee' => $gateway->getFixedFee(),
                'checkout_fields' => $gateway->getCheckoutFields(),
                'mode' => $setting->mode,
            ] : null;
        })->filter()->values();
    }

    public function getAllRegistered(): array
    {
        return array_keys($this->gateways);
    }

    public function invalidateCache(string $name): void
    {
        unset($this->instances[$name]);
    }

    public function isRegistered(string $name): bool
    {
        return isset($this->gateways[$name]);
    }
}
