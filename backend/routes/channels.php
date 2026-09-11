<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('shipments.{shipmentId}', fn ($user, int $shipmentId) => true);
Broadcast::channel('public-shipments.{shipmentId}', fn ($user, int $shipmentId) => true);
Broadcast::channel('conversations.{conversationId}', fn ($user, int $conversationId) => true);
Broadcast::channel('users.{userId}', fn ($user, int $userId) => (int) $user->id === $userId || in_array($user->role, ['admin', 'staff'], true));

Broadcast::channel('guest-chat.{conversationId}', fn () => true);
Broadcast::channel('admin-chats', fn ($user) => in_array($user->role, ['admin', 'staff'], true));
