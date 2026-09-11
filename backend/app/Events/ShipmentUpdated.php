<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Shipment $shipment) {}

    public function broadcastOn(): array
    {
        return [new Channel('shipments.'.$this->shipment->id)];
    }

    public function broadcastAs(): string
    {
        return 'shipment.updated';
    }

    public function broadcastWith(): array
    {
        $shipment = $this->shipment->fresh(['payments', 'trackingEvents', 'driver', 'warehouse', 'attachments']);

        if ($shipment) {
            $shipment->setRelation(
                'attachments',
                $shipment->attachments
                    ->filter(fn ($attachment) => $attachment->isVisibleOnTracking())
                    ->values()
                    ->map(fn ($attachment) => $attachment->publicPayload())
            );
        }

        return [
            'shipment' => $shipment,
        ];
    }
}
