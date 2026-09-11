<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_request_id',
        'provider',
        'provider_reference',
        'amount',
        'currency',
        'fee_amount',
        'net_amount',
        'status',
        'provider_payload',
        'verified_at',
        'webhook_received_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'provider_payload' => 'array',
        'verified_at' => 'datetime',
        'webhook_received_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function auditLogs()
    {
        return $this->hasMany(PaymentAuditLog::class, 'payment_transaction_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}
