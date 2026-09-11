<?php

namespace App\Services;

class QuoteService
{
    public function calculate(array $data): array
    {
        $weight = (float) $data['weight_kg'];
        $value = (float) ($data['declared_value'] ?? 0);
        $originCountry = $data['origin_country'] ?? 'US';
        $destCountry = $data['destination_country'] ?? 'US';
        $international = $originCountry !== $destCountry;
        $service = $data['service_level'] ?? 'domestic_express';

        $levels = config('shipment.service_levels', []);
        $level = $levels[$service] ?? null;

        if ($level) {
            $base = $level['base_rate'];
            $perKg = $level['per_kg_rate'] ?? 7.8;
            $multiplier = $level['multiplier'] ?? 1.25;
            $transitDays = $level['transit_days'] ?? ($international ? 4 : 2);
            $amount = $base + ($weight * $perKg * $multiplier) + ($value * 0.012);
        } else {
            $base = $international ? 85 : 32;
            $perKg = $international ? 6.8 : 3.4;
            $transitDays = $international ? 4 : 2;
            $amount = $base + ($weight * $perKg) + ($value * 0.012);
        }

        return [
            'service_level' => $service,
            'amount' => round($amount, 2),
            'currency' => \App\Support\AppSettings::currency(),
            'deposit_due' => round($amount * 0.2, 2),
            'transit_days' => [$transitDays, $transitDays + 2],
            'estimated_delivery_at' => now()->addDays($transitDays)->toISOString(),
        ];
    }
}
