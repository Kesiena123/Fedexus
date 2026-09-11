<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Collection;

class MapPointService
{
    public function __construct(
        private readonly LocationCoordinateResolver $resolver,
    ) {}

    public function buildForShipment(Shipment $shipment): Collection
    {
        $points = collect();

        foreach (($shipment->routePoints ?? collect()) as $point) {
            $coords = ($point->latitude !== null && $point->longitude !== null)
                ? ['latitude' => (float) $point->latitude, 'longitude' => (float) $point->longitude]
                : $this->resolver->resolveLocation((string) $point->location, $point->country_code);

            if ($coords) {
                $points->push([
                    'label' => $point->label,
                    'location' => $point->location,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'type' => $point->type,
                ]);
            }
        }

        $latestEventId = optional($shipment->trackingEvents->first())->id;
        foreach (($shipment->trackingEvents ?? collect()) as $event) {
            $coords = ($event->latitude !== null && $event->longitude !== null)
                ? ['latitude' => (float) $event->latitude, 'longitude' => (float) $event->longitude]
                : $this->resolver->resolveLocation((string) $event->location, $event->country_code);

            if ($coords) {
                $points->push([
                    'label' => $event->id === $latestEventId ? 'Current Location' : \App\Support\ShipmentStatus::toDisplay($event->status),
                    'location' => $event->location,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'type' => $event->id === $latestEventId ? 'current' : 'transit',
                ]);
            }
        }

        if ($points->isEmpty()) {
            $this->appendOriginDestination($shipment, $points);
        }

        return $points->unique(fn ($p) => $p['latitude'] . '|' . $p['longitude'] . '|' . $p['label'])->values();
    }

    public function settings(): array
    {
        return [
            'defaultLatitude' => (float) \App\Support\AppSettings::mapDefaultLatitude(),
            'defaultLongitude' => (float) \App\Support\AppSettings::mapDefaultLongitude(),
            'zoom' => (int) \App\Support\AppSettings::mapZoom(),
        ];
    }

    private function appendOriginDestination(Shipment $shipment, Collection $points): void
    {
        if ($shipment->origin_address) {
            $coords = $this->resolver->resolveAddress($shipment->origin_address);
            if ($coords) {
                $points->push([
                    'label' => 'Origin',
                    'location' => $shipment->origin_address['city'] ?? ($shipment->origin_address['address'] ?? ''),
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'type' => 'origin',
                ]);
            }
        }
        if ($shipment->destination_address) {
            $coords = $this->resolver->resolveAddress($shipment->destination_address);
            if ($coords) {
                $points->push([
                    'label' => 'Destination',
                    'location' => $shipment->destination_address['city'] ?? ($shipment->destination_address['address'] ?? ''),
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'type' => 'destination',
                ]);
            }
        }
    }
}
