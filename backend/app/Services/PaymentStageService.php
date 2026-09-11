<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Shipment;

class PaymentStageService
{
    private const STAGES = [
        1 => 'Booking Deposit',
        2 => 'Pickup Confirmation',
        3 => 'International Processing',
        4 => 'Destination Hub Processing',
        5 => 'Final Delivery Release',
    ];

    public function createStages(Shipment $shipment): void
    {
        foreach (self::STAGES as $stage => $label) {
            Payment::firstOrCreate(
                ['shipment_id' => $shipment->id, 'stage' => $stage],
                [
                    'label' => $label,
                    'percentage' => 20,
                    'amount' => round(((float) $shipment->quoted_amount) * 0.2, 2),
                    'status' => $stage === 1 ? 'pending' : 'locked',
                    'unlocked_at' => $stage === 1 ? now() : null,
                ]
            );
        }
    }

    public function unlockNextStage(Shipment $shipment): ?Payment
    {
        $next = $shipment->payments()
            ->where('status', 'locked')
            ->orderBy('stage')
            ->first();

        if (! $next || in_array($shipment->status, ['paused', 'cancelled', 'delivered'], true)) {
            return null;
        }

        $next->update(['status' => 'pending', 'unlocked_at' => now()]);

        return $next;
    }
}
