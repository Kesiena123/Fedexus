<?php

namespace App\Services;

use App\Models\Shipment;

class ShipmentReceiptService
{
    public function parseMeta(Shipment $shipment): array
    {
        $meta = $shipment->metadata ?? [];
        return [
            'senderMeta' => $meta['sender'] ?? [],
            'receiverMeta' => $meta['receiver'] ?? [],
            'costs' => $meta['costs'] ?? $meta['cost'] ?? [],
            'pkg' => $meta['package'] ?? ($meta['packages'][0] ?? []),
            'workflow' => $meta['workflow'] ?? [],
            'payment' => $meta['payment'] ?? [],
            'origin' => $shipment->origin_address ?? [],
            'dest' => $shipment->destination_address ?? [],
        ];
    }

    public function calculateTotalCost(Shipment $shipment, array $costs): float
    {
        $total = (float) ($costs['total_cost'] ?? $shipment->quoted_amount ?? 0);
        if ($total > 0) {
            return $total;
        }

        return (float) ($costs['shipping_cost'] ?? 0)
            + (float) ($costs['handling_fee'] ?? 0)
            + (float) ($costs['insurance_fee'] ?? $costs['insurance'] ?? 0)
            + (float) ($costs['customs_fee'] ?? 0)
            + (float) ($costs['tax'] ?? 0)
            + (float) ($costs['additional_charges'] ?? 0)
            - (float) ($costs['discount'] ?? 0);
    }

    public function loadForReceipt(string $trackingNumber): ?Shipment
    {
        return Shipment::with([
            'trackingEvents', 'payments', 'paymentRequests.transactions',
            'paymentRequests.paymentProofs', 'routePoints', 'warehouse',
        ])
            ->where('tracking_number', strtoupper(trim($trackingNumber)))
            ->firstOrFail();
    }
}
