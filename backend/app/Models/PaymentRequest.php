<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    public const STATUSES = [
        'draft', 'payment_required', 'payment_initiated', 'awaiting_verification',
        'processing', 'paid', 'verified', 'cancelled', 'expired', 'failed',
        'refunded', 'archived',
    ];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $attributes = [
        'status' => 'payment_required',
        'priority' => 'normal',
        'is_active' => true,
        'is_archived' => false,
    ];

    protected $fillable = [
        'shipment_id',
        'title',
        'category',
        'priority',
        'reason',
        'description',
        'amount',
        'currency',
        'requested_method',
        'status',
        'is_active',
        'is_archived',
        'secure_token',
        'due_at',
        'paid_at',
        'verified_at',
        'created_by',
        'verified_by',
        'metadata',
        'payment_reference',
        'internal_notes',
        'payment_instructions',
        'duplicated_from_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function duplicatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicated_from_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicated_from_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_archived', false);
    }

    public function scopeArchived(Builder $q): Builder
    {
        return $q->where('is_archived', true);
    }

    public function scopePayable(Builder $q): Builder
    {
        return $q->active()->whereIn('status', ['payment_required', 'payment_initiated', 'awaiting_verification']);
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->whereIn('status', ['paid', 'verified']);
    }

    public function scopeFailed(Builder $q): Builder
    {
        return $q->whereIn('status', ['failed', 'cancelled', 'expired']);
    }

    public function scopeByTracking(Builder $q, string $tracking): Builder
    {
        return $q->whereHas('shipment', fn ($s) => $s->where('tracking_number', $tracking));
    }

    public function isPayable(): bool
    {
        return $this->is_active && ! $this->is_archived
            && in_array($this->status, ['payment_required', 'payment_initiated', 'awaiting_verification']);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['paid', 'verified']);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['paid', 'verified', 'cancelled', 'expired', 'refunded', 'archived']);
    }

    public function statusLabel(): string
    {
        $labels = [
            'draft' => 'Draft',
            'payment_required' => 'Payment Required',
            'payment_initiated' => 'Payment Initiated',
            'awaiting_verification' => 'Awaiting Verification',
            'processing' => 'Processing',
            'paid' => 'Paid',
            'verified' => 'Verified',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'archived' => 'Archived',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function priorityLabel(): string
    {
        return ucfirst($this->priority ?? 'normal');
    }
}
