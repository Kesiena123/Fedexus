<?php

namespace App\Http\Controllers;

use App\Events\GuestChatMessageSent;
use App\Models\AdminSetting;
use App\Models\GuestChatConversation;
use App\Models\Shipment;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestChatController extends Controller
{
    public function info(Request $request): JsonResponse
    {
        $tracking = $request->get('tracking');
        $shipment = Shipment::where('tracking_number', $tracking)->first();

        if (! $shipment) {
            return response()->json(['error' => 'Shipment not found.'], 404);
        }

        $existing = GuestChatConversation::where('shipment_id', $shipment->id)
            ->whereIn('chat_status', [GuestChatConversation::STATUS_OPEN, GuestChatConversation::STATUS_WAITING_ADMIN, GuestChatConversation::STATUS_WAITING_GUEST])
            ->latest()
            ->first();

        $session = $request->session()->get('guest_chat_' . $shipment->id);

        return response()->json([
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'existing_conversation' => $existing ? ['id' => $existing->id, 'token' => $existing->secure_token] : null,
            'session_info' => $session ? ['name' => $session['name'], 'email' => $session['email'], 'phone' => $session['phone'] ?? ''] : null,
        ]);
    }

    public function start(Request $request, NotificationDispatchService $notifier): JsonResponse
    {
        if (! AdminSetting::value('live_chat_enabled', true)) {
            abort(422, 'Live chat is currently disabled.');
        }

        $validator = Validator::make($request->all(), [
            'tracking_number' => ['required', 'string', 'max:80', 'exists:shipments,tracking_number'],
            'guest_name' => ['required', 'string', 'max:140'],
            'guest_email' => ['required', 'email', 'max:180'],
            'guest_phone' => ['nullable', 'string', 'max:60'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $shipment = Shipment::where('tracking_number', $data['tracking_number'])->first();

        $conversation = GuestChatConversation::create([
            'shipment_id' => $shipment->id,
            'tracking_number' => $data['tracking_number'],
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'subject' => 'Support for ' . $data['tracking_number'],
            'secure_token' => Str::random(64),
            'chat_status' => GuestChatConversation::STATUS_WAITING_ADMIN,
            'last_guest_message_at' => now(),
        ]);

        $message = $conversation->messages()->create([
            'sender_type' => 'guest',
            'body' => strip_tags($data['message']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        broadcast(new GuestChatMessageSent($message))->toOthers();

        $request->session()->put('guest_chat_' . $shipment->id, [
            'name' => $data['guest_name'],
            'email' => $data['guest_email'],
            'phone' => $data['guest_phone'] ?? '',
            'token' => $conversation->secure_token,
        ]);

        // Notify all admins about new chat
        $notifyEnabled = AdminSetting::value('notify_admin_new_chat', true);
        if ($notifyEnabled) {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();
            $notifBody = "{$data['guest_name']} opened a chat about {$data['tracking_number']}: " . Str::limit(strip_tags($data['message']), 100);
            foreach ($admins as $admin) {
                $notifier->send($admin, 'New Chat: ' . $data['tracking_number'], $notifBody, [
                    'type' => 'live_chat',
                    'action' => 'new_chat',
                    'conversation_id' => $conversation->id,
                    'tracking_number' => $data['tracking_number'],
                ], ['in_app', 'email']);
            }
        }

        $welcomeMessage = AdminSetting::value('live_chat_welcome_message', 'Welcome! How can we help you with your shipment?');

        return response()->json([
            'conversation' => $conversation->load('messages'),
            'token' => $conversation->secure_token,
            'welcome_message' => $welcomeMessage,
        ], 201);
    }

    public function resume(Request $request): JsonResponse
    {
        $token = $request->get('token');
        $conversation = GuestChatConversation::with('messages.admin')
            ->where('secure_token', $token)
            ->first();

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        // Mark admin messages as delivered when guest reads them
        $conversation->messages()->where('sender_type', 'admin')->whereNull('read_at')->get()->each(function ($msg) {
            $msg->markDelivered();
            $msg->markRead();
        });

        return response()->json([
            'conversation' => $conversation,
            'tracking_number' => $conversation->tracking_number,
        ]);
    }

    public function reply(Request $request, NotificationDispatchService $notifier): JsonResponse
    {
        $token = $request->get('token');
        $conversation = GuestChatConversation::where('secure_token', $token)->first();

        if (! $conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        if (in_array($conversation->chat_status, [GuestChatConversation::STATUS_CLOSED, GuestChatConversation::STATUS_ARCHIVED])) {
            abort(422, 'This conversation is closed.');
        }

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:3000'],
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
            'sender_type' => 'guest',
            'body' => strip_tags($data['message']),
            'attachments' => $attachments ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $conversation->update([
            'chat_status' => GuestChatConversation::STATUS_WAITING_ADMIN,
            'last_guest_message_at' => now(),
            'is_admin_notified' => false,
        ]);

        broadcast(new GuestChatMessageSent($message))->toOthers();

        // Notify admins about new message
        $notifyEnabled = AdminSetting::value('notify_admin_new_chat', true);
        if ($notifyEnabled) {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();
            $notifBody = "{$conversation->guest_name}: " . Str::limit(strip_tags($data['message']), 100);
            foreach ($admins as $admin) {
                $notifier->send($admin, 'New Message: ' . $conversation->tracking_number, $notifBody, [
                    'type' => 'live_chat',
                    'action' => 'new_message',
                    'conversation_id' => $conversation->id,
                    'tracking_number' => $conversation->tracking_number,
                ], ['in_app']);
            }
        }

        return response()->json(['message' => $message->loadMissing('admin')], 201);
    }

    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversation = GuestChatConversation::where('secure_token', $request->token)->firstOrFail();

        $file = $request->file('file');
        $path = $file->store('chat-attachments/' . $conversation->id, 'public');

        return response()->json([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => asset('storage/' . $path),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ], 201);
    }
}
