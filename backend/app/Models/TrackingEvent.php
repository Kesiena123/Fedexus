<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    protected $fillable = [
        'shipment_id',
        'status',
        'location',
        'description',
        'created_by',
        'occurred_at',
        'latitude',
        'longitude',
        'country_code',
        'checkpoint_label',
        'warehouse_name',
        'admin_notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
