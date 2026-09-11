<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestChatMessage extends Model
{
    public const DELIVERY_SENT = 'sent';
    public const DELIVERY_DELIVERED = 'delivered';
    public const DELIVERY_READ = 'read';

    protected $fillable = [
        'guest_chat_conversation_id',
        'sender_type',
        'admin_user_id',
        'body',
        'attachments',
        'read_at',
        'delivery_status',
        'delivered_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(GuestChatConversation::class, 'guest_chat_conversation_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function markDelivered(): void
    {
        if ($this->delivery_status === self::DELIVERY_SENT) {
            $this->update(['delivery_status' => self::DELIVERY_DELIVERED, 'delivered_at' => now()]);
        }
    }

    public function markRead(): void
    {
        $this->update(['delivery_status' => self::DELIVERY_READ, 'read_at' => now()]);
    }

    public function isRead(): bool
    {
        return $this->delivery_status === self::DELIVERY_READ || $this->read_at !== null;
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    public function scopeFromGuest(Builder $q): Builder
    {
        return $q->where('sender_type', 'guest');
    }

    public function scopeFromAdmin(Builder $q): Builder
    {
        return $q->where('sender_type', 'admin');
    }
}
