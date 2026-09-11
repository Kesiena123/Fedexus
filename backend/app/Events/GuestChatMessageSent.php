<?php

namespace App\Events;

use App\Models\GuestChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public GuestChatMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('guest-chat.' . $this->message->guest_chat_conversation_id),
            new Channel('admin-chats'),
        ];
    }

    public function broadcastWith(): array
    {
        $msg = $this->message->loadMissing('admin');

        return [
            'id' => $msg->id,
            'conversation_id' => $msg->guest_chat_conversation_id,
            'sender_type' => $msg->sender_type,
            'admin_name' => $msg->admin?->name,
            'body' => $msg->body,
            'attachments' => $msg->attachments,
            'created_at' => $msg->created_at->toIso8601String(),
            'delivery_status' => $msg->delivery_status,
            'read_at' => $msg->read_at?->toIso8601String(),
            'delivered_at' => $msg->delivered_at?->toIso8601String(),
        ];
    }
}
