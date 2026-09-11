<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    public const FLUTTERWAVE_BASE_GATEWAY = 'flutterwave';

    protected $fillable = [
        'gateway_name',
        'api_key',
        'secret_key',
        'public_key',
        'encryption_key',
        'environment_credentials',
        'webhook_secret',
        'webhook_url',
        'merchant_name',
        'mode',
        'currency',
        'is_active',
        'processing_fee_percent',
        'fixed_fee',
        'min_amount',
        'max_amount',
        'supported_currencies',
        'config',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'secret_key' => 'encrypted',
        'public_key' => 'encrypted',
        'encryption_key' => 'encrypted',
        'environment_credentials' => 'encrypted:array',
        'webhook_secret' => 'encrypted',
        'is_active' => 'boolean',
        'processing_fee_percent' => 'decimal:2',
        'fixed_fee' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'supported_currencies' => 'array',
        'config' => 'array',
    ];

    public function maskedApiKey(): ?string
    {
        return $this->maskValue($this->api_key);
    }

    public function maskedSecretKey(): ?string
    {
        return $this->maskValue($this->secret_key);
    }

    public function maskedPublicKey(): ?string
    {
        return $this->maskValue($this->public_key);
    }

    public function maskedWebhookSecret(): ?string
    {
        return $this->maskValue($this->webhook_secret);
    }

    public function maskedEncryptionKey(): ?string
    {
        return $this->maskValue($this->encryption_key);
    }

    public function isFlutterwave(): bool
    {
        return $this->gateway_name === self::FLUTTERWAVE_BASE_GATEWAY;
    }

    public function flutterwaveWebhookUrl(): string
    {
        return $this->webhook_url ?: url('/api/payments/webhook');
    }

    public function flutterwaveEnvironmentConfig(?string $mode = null): array
    {
        $mode = $mode ?: ($this->mode ?: 'test');

        return $this->environment_credentials[$mode] ?? ($this->config['environments'][$mode] ?? []);
    }

    public function activeFlutterwaveConfig(): array
    {
        return $this->flutterwaveEnvironmentConfig($this->mode);
    }

    public function hasFlutterwaveEnvironmentCredentials(string $mode): bool
    {
        $env = $this->flutterwaveEnvironmentConfig($mode);

        return ! empty($env['public_key']) || ! empty($env['secret_key']) || ! empty($env['encryption_key']) || ! empty($env['webhook_secret']);
    }

    private function maskValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $len = strlen($value);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4).substr($value, -4);
    }
}
