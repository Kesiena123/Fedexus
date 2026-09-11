<?php

declare(strict_types=1);
use App\Models\Shipment;
use App\Models\TrackingEvent;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$shipment = Shipment::where('tracking_number', 'FDX-2026-8K3P91QZ')->first();

if (! $shipment) {
    echo "shipment_not_found\n";
    exit(0);
}

$origin = (array) $shipment->origin_address;
$origin['latitude'] = 36.1627;
$origin['longitude'] = -86.7816;
$shipment->origin_address = $origin;

$destination = (array) $shipment->destination_address;
$destination['latitude'] = 51.5072;
$destination['longitude'] = -0.1276;
$shipment->destination_address = $destination;
$shipment->save();

TrackingEvent::updateOrCreate(
    ['shipment_id' => $shipment->id, 'status' => 'booked'],
    [
        'location' => 'Nashville, TN',
        'description' => 'Shipment booked and deposit captured.',
        'occurred_at' => now()->subDay(),
        'latitude' => 36.1627,
        'longitude' => -86.7816,
    ]
);

TrackingEvent::updateOrCreate(
    ['shipment_id' => $shipment->id, 'status' => 'international_processing'],
    [
        'location' => 'Keflavik, IS',
        'description' => 'International processing checkpoint.',
        'occurred_at' => now()->subHours(6),
        'latitude' => 63.985,
        'longitude' => -22.6056,
    ]
);

TrackingEvent::updateOrCreate(
    ['shipment_id' => $shipment->id, 'status' => 'destination_hub'],
    [
        'location' => 'London, GB',
        'description' => 'Arrived at destination hub.',
        'occurred_at' => now()->subHours(1),
        'latitude' => 51.5072,
        'longitude' => -0.1276,
    ]
);

echo "updated\n";
