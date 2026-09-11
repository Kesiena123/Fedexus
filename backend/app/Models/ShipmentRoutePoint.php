<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentRoutePoint extends Model
{
    protected $fillable = [
        'shipment_id',
        'sort_order',
        'type',
        'label',
        'location',
        'latitude',
        'longitude',
        'country_code',
        'description',
        'arrived_at',
        'departed_at',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'arrived_at' => 'datetime',
        'departed_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
