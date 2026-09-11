<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestChatConversation extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_WAITING_ADMIN = 'waiting_for_admin';
    public const STATUS_WAITING_GUEST = 'waiting_for_guest';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_WAITING_ADMIN,
        self::STATUS_WAITING_GUEST,
        self::STATUS_CLOSED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'shipment_id',
        'tracking_number',
        'guest_name',
        'guest_email',
        'guest_phone',
        'subject',
        'status',
        'chat_status',
        'secure_token',
        'last_guest_message_at',
        'last_admin_message_at',
        'closed_by',
        'closed_at',
        'is_guest_notified',
        'is_admin_notified',
    ];

    protected $casts = [
        'last_guest_message_at' => 'datetime',
        'last_admin_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_guest_notified' => 'boolean',
        'is_admin_notified' => 'boolean',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GuestChatMessage::class)->oldest();
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(GuestChatMessage::class)->latest()->limit(1);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('chat_status', [self::STATUS_OPEN, self::STATUS_WAITING_ADMIN, self::STATUS_WAITING_GUEST]);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('chat_status', self::STATUS_WAITING_ADMIN);
    }

    public function scopeClosed(Builder $q): Builder
    {
        return $q->where('chat_status', self::STATUS_CLOSED);
    }

    public function scopeArchived(Builder $q): Builder
    {
        return $q->where('chat_status', self::STATUS_ARCHIVED);
    }

    public function scopeByTracking(Builder $q, string $tracking): Builder
    {
        return $q->where('tracking_number', $tracking);
    }

    public function scopeOpenForGuest(Builder $q, string $token): Builder
    {
        return $q->where('secure_token', $token)
            ->whereIn('chat_status', [self::STATUS_OPEN, self::STATUS_WAITING_ADMIN, self::STATUS_WAITING_GUEST]);
    }

    public function isActive(): bool
    {
        return in_array($this->chat_status, [self::STATUS_OPEN, self::STATUS_WAITING_ADMIN, self::STATUS_WAITING_GUEST]);
    }

    public function isAwaitingAdmin(): bool
    {
        return $this->chat_status === self::STATUS_WAITING_ADMIN;
    }

    public function isAwaitingGuest(): bool
    {
        return $this->chat_status === self::STATUS_WAITING_GUEST;
    }

    public function unreadCount(): int
    {
        return $this->messages()->where('sender_type', 'guest')->whereNull('read_at')->count();
    }

    public function statusLabel(): string
    {
        return match ($this->chat_status ?? $this->status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_WAITING_ADMIN => 'Waiting for Admin',
            self::STATUS_WAITING_GUEST => 'Waiting for Guest',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_ARCHIVED => 'Archived',
            default => ucfirst($this->chat_status ?? $this->status),
        };
    }

    public function scopeWhereChatStatus(Builder $q, string $status): Builder
    {
        return $q->where('chat_status', $status);
    }
}
