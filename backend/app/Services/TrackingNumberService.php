<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Str;

class TrackingNumberService
{
    public function generate(?string $format = null): string
    {
        $format = strtoupper((string) ($format ?: 'GEX'));

        do {
            $tracking = match ($format) {
                'GEX' => 'GEX-'.now()->year.'-'.Str::upper(Str::random(8)),
                'FDX' => 'FDX-'.now()->year.'-'.Str::upper(Str::random(8)),
                'TRK' => 'TRK-'.Str::upper(Str::random(10)),
                default => 'LOG-'.Str::upper(Str::random(10)),
            };
        } while (Shipment::where('tracking_number', $tracking)->exists());

        return $tracking;
    }
}
