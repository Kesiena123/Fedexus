<?php

namespace App\Http\Controllers;

use App\Events\GuestChatMessageSent;
use App\Models\AdminAuditLog;
use App\Models\GuestChatConversation;
use App\Models\GuestChatMessage;
use App\Models\Shipment;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\NotificationDispatchService;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminGuestChatController extends Controller
{
    private function authorize(): void
    {
        AdminPermissions::authorize(Auth::user(), AdminPermissions::DASHBOARD_VIEW);
    }

    public function conversations(Request $request): JsonResponse
    {
        $this->authorize();

        $query = GuestChatConversation::with(['shipment', 'latestMessage']);

        $filter = $request->get('filter', 'active');
        if ($filter === 'unread') {
            $query->unread();
        } elseif ($filter === 'closed') {
            $query->closed();
        } elseif ($filter === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('guest_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%");
            });
        }

        $conversations = $query->latest('updated_at')->paginate(25)->withQueryString();

        $counts = [
            'active' => GuestChatConversation::active()->count(),
            'unread' => GuestChatConversation::unread()->count(),
            'closed' => GuestChatConversation::closed()->count(),
            'archived' => GuestChatConversation::archived()->count(),
            'total' => GuestChatConversation::count(),
        ];

        return response()->json([
            'conversations' => $conversations->items(),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'total' => $conversations->total(),
                'per_page' => $conversations->perPage(),
            ],
            'counts' => $counts,
            'filter' => $filter,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize();

        $conversation = GuestChatConversation::with([
            'messages.admin',
            'shipment.trackingEvents',
            'shipment.paymentRequests.transactions',
            'shipment.routePoints',
        ])->findOrFail($id);

        // Mark guest messages as delivered when admin views them
        $conversation->messages()->where('sender_type', 'guest')->whereNull('read_at')->get()->each(function ($msg) {
            $msg->markDelivered();
            $msg->markRead();
        });

        $conversation->messages()->where('sender_type', 'admin')->whereNull('read_at')->get()->each->markRead();

        $shipmentData = null;
        if ($conversation->shipment) {
            $s = $conversation->shipment;
            $shipmentData = [
                'id' => $s->id,
                'tracking_number' => $s->tracking_number,
                'status' => $s->status,
                'service_level' => $s->service_level,
                'sender_name' => $s->sender_name,
                'recipient_name' => $s->recipient_name,
                'origin_address' => $s->origin_address,
                'destination_address' => $s->destination_address,
                'weight_kg' => $s->weight_kg,
                'declared_value' => $s->declared_value,
                'estimated_delivery_at' => $s->estimated_delivery_at?->format('M d, Y'),
                'created_at' => $s->created_at->format('M d, Y'),
                'payment_requests' => $s->paymentRequests->map(fn ($pr) => [
                    'id' => $pr->id,
                    'title' => $pr->title,
                    'amount' => $pr->amount,
                    'currency' => $pr->currency,
                    'status' => $pr->status,
                    'is_active' => $pr->is_active,
                ]),
                'tracking_events' => $s->trackingEvents->map(fn ($e) => [
                    'status' => $e->status,
                    'location' => $e->location,
                    'description' => $e->description,
                    'occurred_at' => $e->occurred_at?->format('M d, Y H:i'),
                ]),
            ];
        }

        return response()->json([
            'conversation' => $conversation,
            'shipment' => $shipmentData,
        ]);
    }

    public function reply(Request $request, int $id, AdminAuditLogger $audit, NotificationDispatchService $notifier): JsonResponse
    {
        $this->authorize();
        $user = Auth::user();

        $conversation = GuestChatConversation::findOrFail($id);

        if (in_array($conversation->chat_status, [GuestChatConversation::STATUS_CLOSED, GuestChatConversation::STATUS_ARCHIVED])) {
            abort(422, 'This conversation is closed.');
        }

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $attachments = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('chat-attachments/' . $conversation->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $message = $conversation->messages()->create([
            'sender_type' => 'admin',
            'admin_user_id' => $user->id,
            'body' => strip_tags($data['message']),
            'attachments' => $attachments ?: null,
        ]);

        $conversation->update([
            'chat_status' => GuestChatConversation::STATUS_WAITING_GUEST,
            'last_admin_message_at' => now(),
            'is_guest_notified' => false,
        ]);

        broadcast(new GuestChatMessageSent($message))->toOthers();

        $audit->log($request, $user, 'live_chat.replied', [
            'target_type' => 'guest_chat_message',
            'target_id' => $message->id,
        ]);

        // Notify guest via email about admin reply
        if ($conversation->guest_email) {
            $notifier->send(
                User::where('email', $conversation->guest_email)->first() ?? new User(['email' => $conversation->guest_email]),
                'Support Reply: ' . $conversation->tracking_number,
                "You have a new reply from our support team regarding shipment {$conversation->tracking_number}:\n\n" . strip_tags($data['message']),
                [
                    'type' => 'live_chat',
                    'action' => 'admin_replied',
                    'conversation_id' => $conversation->id,
                    'tracking_number' => $conversation->tracking_number,
                ],
                ['email'],
                ['email' => $conversation->guest_email]
            );
        }

        return response()->json(['message' => $message->loadMissing('admin')], 201);
    }

    public function close(int $id, Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorize();
        $user = Auth::user();
        $conversation = GuestChatConversation::findOrFail($id);

        $conversation->update([
            'chat_status' => GuestChatConversation::STATUS_CLOSED,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $audit->log($request, $user, 'live_chat.closed', [
            'target_type' => 'guest_chat_conversation',
            'target_id' => $conversation->id,
        ]);

        return response()->json(['status' => 'closed']);
    }

    public function reopen(int $id, Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorize();
        $user = Auth::user();
        $conversation = GuestChatConversation::findOrFail($id);

        $conversation->update([
            'chat_status' => GuestChatConversation::STATUS_WAITING_ADMIN,
            'closed_by' => null,
            'closed_at' => null,
        ]);

        $audit->log($request, $user, 'live_chat.reopened', [
            'target_type' => 'guest_chat_conversation',
            'target_id' => $conversation->id,
        ]);

        return response()->json(['status' => 'reopened']);
    }

    public function archive(int $id, Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorize();
        $user = Auth::user();
        $conversation = GuestChatConversation::findOrFail($id);

        $conversation->update(['chat_status' => GuestChatConversation::STATUS_ARCHIVED]);

        $audit->log($request, $user, 'live_chat.archived', [
            'target_type' => 'guest_chat_conversation',
            'target_id' => $conversation->id,
        ]);

        return response()->json(['status' => 'archived']);
    }

    public function destroy(int $id, Request $request, AdminAuditLogger $audit): JsonResponse
    {
        $this->authorize();
        $user = Auth::user();

        $conversation = GuestChatConversation::findOrFail($id);
        $conversation->messages()->delete();
        $conversation->delete();

        $audit->log($request, $user, 'live_chat.deleted', [
            'target_type' => 'guest_chat_conversation',
            'target_id' => $conversation->id,
        ]);

        return response()->json(['status' => 'deleted']);
    }

    public function markRead(int $id): JsonResponse
    {
        $this->authorize();

        $conversation = GuestChatConversation::findOrFail($id);
        $conversation->messages()->where('sender_type', 'guest')->whereNull('read_at')->get()->each(function ($msg) {
            $msg->markDelivered();
            $msg->markRead();
        });

        $conversation->update(['chat_status' => GuestChatConversation::STATUS_OPEN]);

        return response()->json(['status' => 'read']);
    }
}
