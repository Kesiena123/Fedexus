<?php

namespace App\Support;

class ShipmentStatus
{
    public const ORDERED = 'CREATED';

    public const PENDING = 'PENDING';

    public const PICKED_UP = 'PICKED_UP';

    public const IN_TRANSIT = 'IN_TRANSIT';

    public const ARRIVED_AT_FACILITY = 'ARRIVED_AT_FACILITY';

    public const CUSTOMS_CLEARANCE = 'CUSTOMS_CLEARANCE';

    public const OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';

    public const DELIVERED = 'DELIVERED';

    public const DELAYED = 'DELAYED';

    public const ON_HOLD = 'ON_HOLD';

    public const CANCELLED = 'CANCELLED';

    public const RETURNED = 'RETURNED';

    public const EXCEPTION = 'EXCEPTION';

    private static array $dbToDisplay = [
        'shipment_requested' => self::ORDERED,
        'admin_review' => self::ORDERED,
        'approved' => self::PENDING,
        'rejected' => self::CANCELLED,
        'booked' => self::PENDING,
        'pickup_scheduled' => self::PENDING,
        'picked_up' => self::PICKED_UP,
        'warehouse_processing' => self::IN_TRANSIT,
        'international_processing' => self::IN_TRANSIT,
        'destination_hub' => self::ARRIVED_AT_FACILITY,
        'out_for_delivery' => self::OUT_FOR_DELIVERY,
        'delivered' => self::DELIVERED,
        'paused' => self::ON_HOLD,
        'exception' => self::EXCEPTION,
        'cancelled' => self::CANCELLED,
    ];

    private static array $publicToDb = [
        self::ORDERED => 'booked',
        self::PENDING => 'booked',
        self::PICKED_UP => 'picked_up',
        self::IN_TRANSIT => 'international_processing',
        self::ARRIVED_AT_FACILITY => 'destination_hub',
        self::CUSTOMS_CLEARANCE => 'international_processing',
        self::OUT_FOR_DELIVERY => 'out_for_delivery',
        self::DELIVERED => 'delivered',
        self::DELAYED => 'exception',
        self::ON_HOLD => 'paused',
        self::CANCELLED => 'cancelled',
        self::RETURNED => 'exception',
        self::EXCEPTION => 'exception',
    ];

    private static array $displayLabels = [
        self::ORDERED => 'Shipment Created',
        self::PENDING => 'Shipment Pending',
        self::PICKED_UP => 'Picked Up',
        self::IN_TRANSIT => 'In Transit',
        self::ARRIVED_AT_FACILITY => 'Arrived at Facility',
        self::CUSTOMS_CLEARANCE => 'Customs Clearance',
        self::OUT_FOR_DELIVERY => 'Out for Delivery',
        self::DELIVERED => 'Delivered',
        self::DELAYED => 'Delayed',
        self::ON_HOLD => 'On Hold',
        self::CANCELLED => 'Cancelled',
        self::RETURNED => 'Returned',
        self::EXCEPTION => 'Exception',
    ];

    private static array $displayIcons = [
        self::ORDERED => 'package',
        self::PENDING => 'clock',
        self::PICKED_UP => 'truck',
        self::IN_TRANSIT => 'plane',
        self::ARRIVED_AT_FACILITY => 'warehouse',
        self::CUSTOMS_CLEARANCE => 'shield',
        self::OUT_FOR_DELIVERY => 'truck',
        self::DELIVERED => 'check-circle',
        self::DELAYED => 'alert-circle',
        self::ON_HOLD => 'pause-circle',
        self::CANCELLED => 'x-circle',
        self::RETURNED => 'refresh',
        self::EXCEPTION => 'alert-triangle',
    ];

    private static array $displayColors = [
        self::ORDERED => 'azure',
        self::PENDING => 'navy',
        self::PICKED_UP => 'azure',
        self::IN_TRANSIT => 'gold',
        self::ARRIVED_AT_FACILITY => 'azure',
        self::CUSTOMS_CLEARANCE => 'navy',
        self::OUT_FOR_DELIVERY => 'gold',
        self::DELIVERED => 'emerald',
        self::DELAYED => 'red',
        self::ON_HOLD => 'amber',
        self::CANCELLED => 'red',
        self::RETURNED => 'navy',
        self::EXCEPTION => 'red',
    ];

    public static function toDisplay(string $dbStatus): string
    {
        $display = self::$dbToDisplay[$dbStatus] ?? $dbStatus;

        return self::$displayLabels[$display] ?? str_replace('_', ' ', ucwords($dbStatus, '_'));
    }

    public static function toCode(string $dbStatus): string
    {
        return self::$dbToDisplay[$dbStatus] ?? $dbStatus;
    }

    public static function label(string $displayStatus): string
    {
        return self::$displayLabels[$displayStatus] ?? str_replace('_', ' ', ucwords($displayStatus, '_'));
    }

    public static function icon(string $displayStatus): string
    {
        return self::$displayIcons[$displayStatus] ?? 'package';
    }

    public static function color(string $displayStatus): string
    {
        return self::$displayColors[$displayStatus] ?? 'navy';
    }

    public static function timelineSteps(): array
    {
        return [
            self::ORDERED,
            self::PENDING,
            self::PICKED_UP,
            self::IN_TRANSIT,
            self::ARRIVED_AT_FACILITY,
            self::CUSTOMS_CLEARANCE,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED,
        ];
    }

    public static function databaseStatuses(): array
    {
        return array_values(array_unique(array_keys(self::$dbToDisplay)));
    }

    public static function normalizeForDatabase(string $status): string
    {
        $normalized = strtoupper(trim(str_replace([' ', '-'], '_', $status)));

        return self::$publicToDb[$normalized] ?? strtolower($normalized);
    }
}
