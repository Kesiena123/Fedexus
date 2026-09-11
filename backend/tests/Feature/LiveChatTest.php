<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\GuestChatConversation;
use App\Models\GuestChatMessage;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LiveChatTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Shipment $shipment;
    private string $tracking;

    protected function setUp(): void
    {
        parent::setUp();

        AdminSetting::updateOrCreate(['key' => 'live_chat_enabled'], ['value' => '1', 'type' => 'boolean']);
        AdminSetting::updateOrCreate(['key' => 'notify_admin_new_chat'], ['value' => '1', 'type' => 'boolean']);
        AdminSetting::updateOrCreate(['key' => 'live_chat_welcome_message'], ['value' => 'Welcome!', 'type' => 'string']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@livechat.test',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        $this->shipment = Shipment::create([
            'user_id' => $this->admin->id,
            'tracking_number' => 'TST-CHAT-000001',
            'status' => 'approved',
            'service_level' => 'domestic_express',
            'sender_name' => 'Alice',
            'recipient_name' => 'Bob',
            'origin_address' => ['city' => 'Memphis', 'country' => 'US'],
            'destination_address' => ['city' => 'Austin', 'country' => 'US'],
            'weight_kg' => 2,
            'declared_value' => 100,
            'quoted_amount' => 50,
        ]);

        $this->tracking = $this->shipment->tracking_number;
    }

    // ─── GUEST CHAT ENDPOINTS ────────────────────────────────────────

    public function test_chat_info_returns_404_for_unknown_tracking(): void
    {
        $response = $this->getJson('/api/chat/info?tracking=DOES-NOT-EXIST');
        $response->assertStatus(404)->assertJsonPath('error', 'Shipment not found.');
    }

    public function test_chat_info_returns_no_existing_conversation_when_none(): void
    {
        $response = $this->getJson('/api/chat/info?tracking=' . $this->tracking);
        $response->assertOk();
        $response->assertJsonPath('tracking_number', $this->tracking);
        $this->assertNull($response->json('existing_conversation'));
    }

    public function test_chat_info_returns_existing_active_conversation(): void
    {
        $conv = GuestChatConversation::create([
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->tracking,
            'guest_name' => 'Charlie',
            'guest_email' => 'charlie@test.com',
            'secure_token' => 'test-token-123',
            'chat_status' => GuestChatConversation::STATUS_WAITING_ADMIN,
        ]);

        $response = $this->getJson('/api/chat/info?tracking=' . $this->tracking);
        $response->assertOk();
        $this->assertEquals($conv->id, $response->json('existing_conversation.id'));
        $this->assertEquals('test-token-123', $response->json('existing_conversation.token'));
    }

    public function test_chat_info_ignores_closed_conversations(): void
    {
        GuestChatConversation::create([
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->tracking,
            'guest_name' => 'Charlie',
            'guest_email' => 'charlie@test.com',
            'secure_token' => 'closed-token',
            'chat_status' => GuestChatConversation::STATUS_CLOSED,
        ]);

        $response = $this->getJson('/api/chat/info?tracking=' . $this->tracking);
        $response->assertOk();
        $this->assertNull($response->json('existing_conversation'));
    }

    public function test_chat_start_requires_valid_tracking(): void
    {
        $response = $this->postJson('/api/chat/start', [
            'tracking_number' => 'INVALID',
            'guest_name' => 'Dana',
            'guest_email' => 'dana@test.com',
            'message' => 'Help!',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tracking_number');
    }

    public function test_chat_start_creates_conversation_and_message(): void
    {
        $response = $this->postJson('/api/chat/start', [
            'tracking_number' => $this->tracking,
            'guest_name' => 'Dana',
            'guest_email' => 'dana@test.com',
            'guest_phone' => '+1234567890',
            'message' => 'Where is my package?',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'conversation' => ['id', 'chat_status', 'messages'],
            'token',
            'welcome_message',
        ]);
        $this->assertEquals('waiting_for_admin', $response->json('conversation.chat_status'));
        $this->assertCount(1, $response->json('conversation.messages'));
        $this->assertEquals('Where is my package?', $response->json('conversation.messages.0.body'));

        $this->assertDatabaseHas('guest_chat_conversations', [
            'tracking_number' => $this->tracking,
            'guest_name' => 'Dana',
            'guest_email' => 'dana@test.com',
            'chat_status' => 'waiting_for_admin',
        ]);
    }

    public function test_chat_start_saves_session(): void
    {
        $response = $this->postJson('/api/chat/start', [
            'tracking_number' => $this->tracking,
            'guest_name' => 'Eve',
            'guest_email' => 'eve@test.com',
            'message' => 'Hi',
        ]);

        $response->assertStatus(201);
        $token = $response->json('token');

        $this->assertNotNull($token);
        $this->assertTrue(strlen($token) > 20);
    }

    public function test_chat_start_validates_required_fields(): void
    {
        $response = $this->postJson('/api/chat/start', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tracking_number', 'guest_name', 'guest_email', 'message']);
    }

    public function test_chat_start_validates_email_format(): void
    {
        $response = $this->postJson('/api/chat/start', [
            'tracking_number' => $this->tracking,
            'guest_name' => 'Frank',
            'guest_email' => 'not-an-email',
            'message' => 'Hi',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('guest_email');
    }

    public function test_chat_resume_returns_404_for_invalid_token(): void
    {
        $response = $this->getJson('/api/chat/resume?token=invalid-token');
        $response->assertStatus(404);
    }

    public function test_chat_resume_returns_conversation_and_marks_admin_messages_read(): void
    {
        $conv = GuestChatConversation::create([
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->tracking,
            'guest_name' => 'Grace',
            'guest_email' => 'grace@test.com',
            'secure_token' => 'resume-token',
            'chat_status' => GuestChatConversation::STATUS_OPEN,
        ]);

        $msg = $conv->messages()->create([
            'sender_type' => 'admin',
            'admin_user_id' => $this->admin->id,
            'body' => 'We are on it!',
            'delivery_status' => GuestChatMessage::DELIVERY_SENT,
        ]);

        $response = $this->getJson('/api/chat/resume?token=resume-token');
        $response->assertOk();
        $response->assertJsonPath('conversation.id', $conv->id);

        $msg->refresh();
        $this->assertEquals(GuestChatMessage::DELIVERY_READ, $msg->delivery_status);
        $this->assertNotNull($msg->read_at);
        $this->assertNotNull($msg->delivered_at);
    }

    public function test_chat_reply_adds_message(): void
    {
        $conv = GuestChatConversation::create([
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->tracking,
            'guest_name' => 'Heidi',
            'guest_email' => 'heidi@test.com',
            'secure_token' => 'reply-token',
            'chat_status' => GuestChatConversation::STATUS_OPEN,
        ]);

        $response = $this->postJson('/api/chat/reply', [
            'token' => 'reply-token',
            'message' => 'Any update?',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('Any update?', $response->json('message.body'));
        $this->assertEquals('guest', $response->json('message.sender_type'));

        $this->assertDatabaseHas('guest_chat_messages', [
            'guest_chat_conversation_id' => $conv->id,
            'body' => 'Any update?',
            'sender_type' => 'guest',
        ]);

        $conv->refresh();
        $this->assertEquals(GuestChatConversation::STATUS_WAITING_ADMIN, $conv->chat_status);
    }

    public function test_chat_reply_fails_for_closed_conversation(): void
    {
        $conv = GuestChatConversation::create([
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->tracking,
            'guest_name' => 'Ivan',
            'guest_email' => 'ivan@test.com',
            'secure_token' => 'closed-reply-token',
            'chat_status' => GuestChatConversation::STATUS_CLOSED,
        ]);

        $response = $this->postJson('/api/chat/reply', [
            'token' => 'closed-reply-token',
            'message' => 'Hello?',
        ]);

        $response->assertStatus(422);
    }

    public function test_chat_reply_fails_without_token(): void
    {
        $response = $this->postJson('/api/chat/reply', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(404);
    }

    // ─── ADMIN CHAT ENDPOINTS ────────────────────────────────────────

    private function adminJson(string $method, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->json($method, $uri, $data);
    }

    private function makeConversation(array $overrides = []): GuestChatConversation
    {
        return GuestChatConversation::create(array_merge([
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->tracking,
            'guest_name' => 'Jack',
            'guest_email' => 'jack@test.com',
            'secure_token' => 'admin-test-' . uniqid(),
            'chat_status' => GuestChatConversation::STATUS_WAITING_ADMIN,
        ], $overrides));
    }

    public function test_admin_conversations_requires_auth(): void
    {
        $this->getJson('/admin/api/live-chat/conversations')
            ->assertStatus(401);
    }

    public function test_admin_conversations_filter_active(): void
    {
        $active = $this->makeConversation();
        $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_CLOSED]);

        $response = $this->adminJson('GET', '/admin/api/live-chat/conversations?filter=active');
        $response->assertOk();
        $ids = collect($response->json('conversations'))->pluck('id')->toArray();
        $this->assertContains($active->id, $ids);
    }

    public function test_admin_conversations_filter_closed(): void
    {
        $this->makeConversation();
        $closed = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_CLOSED]);

        $response = $this->adminJson('GET', '/admin/api/live-chat/conversations?filter=closed');
        $response->assertOk();
        $ids = collect($response->json('conversations'))->pluck('id')->toArray();
        $this->assertContains($closed->id, $ids);
    }

    public function test_admin_conversations_filter_unread(): void
    {
        $unread = $this->makeConversation();
        $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_OPEN]);

        $response = $this->adminJson('GET', '/admin/api/live-chat/conversations?filter=unread');
        $response->assertOk();
        $ids = collect($response->json('conversations'))->pluck('id')->toArray();
        $this->assertContains($unread->id, $ids);
    }

    public function test_admin_conversations_searches_by_tracking(): void
    {
        $conv = $this->makeConversation();
        $this->makeConversation(['tracking_number' => 'OTHER-TRK-999']);

        $response = $this->adminJson('GET', '/admin/api/live-chat/conversations?search=' . $conv->tracking_number);
        $response->assertOk();
        $this->assertCount(1, $response->json('conversations'));
    }

    public function test_admin_conversations_searches_by_name(): void
    {
        $this->makeConversation();
        $this->makeConversation(['guest_name' => 'Zara']);

        $response = $this->adminJson('GET', '/admin/api/live-chat/conversations?search=Zara');
        $response->assertOk();
        $this->assertCount(1, $response->json('conversations'));
    }

    public function test_admin_show_returns_conversation_with_messages(): void
    {
        $conv = $this->makeConversation();
        $conv->messages()->create(['sender_type' => 'guest', 'body' => 'Help!']);

        $response = $this->adminJson('GET', "/admin/api/live-chat/conversations/{$conv->id}");
        $response->assertOk();
        $response->assertJsonPath('conversation.id', $conv->id);
        $this->assertCount(1, $response->json('conversation.messages'));
        $this->assertNotNull($response->json('shipment'));
        $this->assertEquals($this->tracking, $response->json('shipment.tracking_number'));
    }

    public function test_admin_show_marks_guest_messages_as_read(): void
    {
        $conv = $this->makeConversation();
        $msg = $conv->messages()->create([
            'sender_type' => 'guest',
            'body' => 'Read me',
            'delivery_status' => GuestChatMessage::DELIVERY_SENT,
        ]);

        $this->adminJson('GET', "/admin/api/live-chat/conversations/{$conv->id}");
        $msg->refresh();
        $this->assertEquals(GuestChatMessage::DELIVERY_READ, $msg->delivery_status);
        $this->assertNotNull($msg->read_at);
    }

    public function test_admin_reply_creates_admin_message(): void
    {
        $conv = $this->makeConversation();

        $response = $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/reply", [
            'message' => 'We are helping you!',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('We are helping you!', $response->json('message.body'));
        $this->assertEquals('admin', $response->json('message.sender_type'));

        $this->assertDatabaseHas('guest_chat_messages', [
            'guest_chat_conversation_id' => $conv->id,
            'body' => 'We are helping you!',
            'sender_type' => 'admin',
            'admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_reply_updates_conversation_status(): void
    {
        $conv = $this->makeConversation();

        $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/reply", [
            'message' => 'Checking...',
        ]);

        $conv->refresh();
        $this->assertEquals(GuestChatConversation::STATUS_WAITING_GUEST, $conv->chat_status);
    }

    public function test_admin_reply_fails_for_closed_conversation(): void
    {
        $conv = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_CLOSED]);

        $response = $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/reply", [
            'message' => 'Hello again',
        ]);
        $response->assertStatus(422);
    }

    public function test_admin_close_marks_conversation_closed(): void
    {
        $conv = $this->makeConversation();

        $response = $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/close");
        $response->assertOk()->assertJsonPath('status', 'closed');

        $conv->refresh();
        $this->assertEquals(GuestChatConversation::STATUS_CLOSED, $conv->chat_status);
        $this->assertEquals($this->admin->id, $conv->closed_by);
        $this->assertNotNull($conv->closed_at);
    }

    public function test_admin_reopen_restores_conversation(): void
    {
        $conv = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_CLOSED]);

        $response = $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/reopen");
        $response->assertOk()->assertJsonPath('status', 'reopened');

        $conv->refresh();
        $this->assertEquals(GuestChatConversation::STATUS_WAITING_ADMIN, $conv->chat_status);
        $this->assertNull($conv->closed_by);
        $this->assertNull($conv->closed_at);
    }

    public function test_admin_archive_archives_conversation(): void
    {
        $conv = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_CLOSED]);

        $response = $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/archive");
        $response->assertOk()->assertJsonPath('status', 'archived');

        $conv->refresh();
        $this->assertEquals(GuestChatConversation::STATUS_ARCHIVED, $conv->chat_status);
    }

    public function test_admin_destroy_deletes_conversation_and_messages(): void
    {
        $conv = $this->makeConversation();
        $conv->messages()->create(['sender_type' => 'guest', 'body' => 'Delete me']);

        $response = $this->adminJson('DELETE', "/admin/api/live-chat/conversations/{$conv->id}");
        $response->assertOk()->assertJsonPath('status', 'deleted');

        $this->assertDatabaseMissing('guest_chat_conversations', ['id' => $conv->id]);
        $this->assertDatabaseMissing('guest_chat_messages', ['guest_chat_conversation_id' => $conv->id]);
    }

    public function test_admin_mark_read_updates_messages(): void
    {
        $conv = $this->makeConversation();
        $msg = $conv->messages()->create([
            'sender_type' => 'guest',
            'body' => 'Unread',
            'delivery_status' => GuestChatMessage::DELIVERY_SENT,
        ]);

        $response = $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/mark-read");
        $response->assertOk()->assertJsonPath('status', 'read');

        $msg->refresh();
        $this->assertEquals(GuestChatMessage::DELIVERY_READ, $msg->delivery_status);
        $this->assertNotNull($msg->read_at);
        $this->assertNotNull($msg->delivered_at);
    }

    public function test_admin_mark_read_sets_conversation_open(): void
    {
        $conv = $this->makeConversation();

        $this->adminJson('POST', "/admin/api/live-chat/conversations/{$conv->id}/mark-read");

        $conv->refresh();
        $this->assertEquals(GuestChatConversation::STATUS_OPEN, $conv->chat_status);
    }

    // ─── MODEL SCOPES ────────────────────────────────────────────────

    public function test_conversation_active_scope(): void
    {
        $open = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_OPEN]);
        $waiting = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_WAITING_ADMIN]);
        $replied = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_WAITING_GUEST]);
        $closed = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_CLOSED]);
        $archived = $this->makeConversation(['chat_status' => GuestChatConversation::STATUS_ARCHIVED]);

        $activeIds = GuestChatConversation::active()->pluck('id')->toArray();
        $this->assertContains($open->id, $activeIds);
        $this->assertContains($waiting->id, $activeIds);
        $this->assertContains($replied->id, $activeIds);
        $this->assertNotContains($closed->id, $activeIds);
        $this->assertNotContains($archived->id, $activeIds);

        $closedIds = GuestChatConversation::closed()->pluck('id')->toArray();
        $this->assertContains($closed->id, $closedIds);
        $this->assertNotContains($open->id, $closedIds);
    }

    public function test_conversation_status_label(): void
    {
        $conv = new GuestChatConversation(['chat_status' => GuestChatConversation::STATUS_OPEN]);
        $this->assertEquals('Open', $conv->statusLabel());

        $conv->chat_status = GuestChatConversation::STATUS_WAITING_ADMIN;
        $this->assertEquals('Waiting for Admin', $conv->statusLabel());

        $conv->chat_status = GuestChatConversation::STATUS_CLOSED;
        $this->assertEquals('Closed', $conv->statusLabel());
    }

    public function test_conversation_is_active(): void
    {
        $conv = new GuestChatConversation(['chat_status' => GuestChatConversation::STATUS_OPEN]);
        $this->assertTrue($conv->isActive());

        $conv->chat_status = GuestChatConversation::STATUS_CLOSED;
        $this->assertFalse($conv->isActive());
    }
}
