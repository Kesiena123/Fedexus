<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'shipment_id', 'stage', 'label', 'percentage', 'amount', 'status', 'provider',
        'provider_reference', 'paid_at', 'unlocked_at', 'verified_at', 'verified_by',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'verified_at' => 'datetime',
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
