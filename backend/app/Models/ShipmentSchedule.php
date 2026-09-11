<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentSchedule extends Model
{
    protected $fillable = ['shipment_id', 'type', 'window_start', 'window_end', 'address', 'instructions', 'status'];

    protected $casts = ['address' => 'array', 'window_start' => 'datetime', 'window_end' => 'datetime'];
}
