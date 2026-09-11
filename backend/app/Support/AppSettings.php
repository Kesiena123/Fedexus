<?php

namespace App\Support;

use App\Models\AdminSetting;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;

class AppSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return AdminSetting::value($key, $default);
    }

    public static function set(string $key, string $value, string $type = 'string', string $group = 'app', string $label = ''): void
    {
        AdminSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group, 'label' => $label ?: ucwords(str_replace('_', ' ', $key))]
        );
    }

    public static function companyName(): string
    {
        return (string) self::get('company_name', config('app.name'));
    }

    public static function companyEmail(): string
    {
        return (string) self::get('company_email', config('mail.from.address'));
    }

    public static function companyPhone(): string
    {
        return (string) self::get('company_phone', '');
    }

    public static function currency(): string
    {
        return (string) self::get('currency', 'USD');
    }

    public static function currencySymbol(): string
    {
        $symbols = ['USD' => '$', 'EUR' => 'EUR ', 'GBP' => 'GBP ', 'NGN' => 'NGN ', 'GHS' => 'GHS ', 'KES' => 'KSh ', 'ZAR' => 'R'];

        return $symbols[self::currency()] ?? '$';
    }

    public static function logo(): string
    {
        return (string) self::get('logo', '');
    }

    public static function favicon(): string
    {
        return (string) self::get('favicon', asset('favicon.ico'));
    }

    public static function mapProvider(): string
    {
        return (string) self::get('map_provider', 'openstreetmap');
    }

    public static function mapDefaultLatitude(): float
    {
        return (float) self::get('map_default_latitude', 39.8283);
    }

    public static function mapDefaultLongitude(): float
    {
        return (float) self::get('map_default_longitude', -98.5795);
    }

    public static function mapZoom(): int
    {
        return max(1, min(19, (int) self::get('map_zoom', 4)));
    }

    public static function timezone(): string
    {
        return (string) self::get('timezone', 'UTC');
    }

    public static function dateFormat(): string
    {
        return (string) self::get('date_format', 'M d, Y');
    }

    public static function address(): string
    {
        return (string) self::get('business_address', '');
    }

    public static function footer(): string
    {
        return (string) self::get('footer', '(c) '.date('Y').' '.self::companyName().'. All rights reserved.');
    }

    public static function trackingEnabled(): bool
    {
        return (bool) self::get('public_tracking_enabled', true);
    }

    public static function receiptBranding(): string
    {
        return (string) self::get('receipt_branding', 'full');
    }

    public static function websiteUrl(): string
    {
        return (string) self::get('website_url', config('app.url'));
    }

    public static function formatDate($date): string
    {
        if (! $date) {
            return '';
        }

        if ($date instanceof Carbon || $date instanceof SupportCarbon) {
            return $date->format(self::dateFormat());
        }

        if (is_string($date)) {
            return Carbon::parse($date)->format(self::dateFormat());
        }

        return (string) $date;
    }

    public static function formatMoney(float $amount): string
    {
        return self::currencySymbol().number_format($amount, 2);
    }
}
