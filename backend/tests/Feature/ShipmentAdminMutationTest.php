<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentAttachment;
use App\Models\User;
use App\Support\AdminSecurity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShipmentAdminMutationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_tracking_only_returns_customer_visible_package_images_and_tracking_assets(): void
    {
        Storage::fake('public');

        $customer = User::create([
            'name' => 'Tracking Customer',
            'email' => 'tracking-customer@example.test',
            'password' => 'Password123!',
            'role' => 'customer',
        ]);

        $shipment = Shipment::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'service_level' => 'freight',
            'sender_name' => 'Sender Name',
            'recipient_name' => 'Recipient Name',
            'origin_address' => ['city' => 'Memphis', 'country' => 'US', 'postal_code' => '38116'],
            'destination_address' => ['city' => 'Austin', 'country' => 'US', 'postal_code' => '73301'],
            'weight_kg' => 12.5,
            'declared_value' => 450,
            'quoted_amount' => 120,
            'tracking_number' => 'FDX-2026-ABC12345',
            'metadata' => [
                'shipment_reference' => 'SH-TRACK-1001',
                'packages' => [
                    [
                        'id' => 'pkg-1',
                        'packageName' => 'Enterprise Crate',
                        'description' => 'Protected electronics',
                        'category' => 'Electronics',
                        'packageType' => 'Crate',
                        'quantity' => 1,
                        'weight' => 12.5,
                        'length' => 30,
                        'width' => 20,
                        'height' => 15,
                        'declaredValue' => 450,
                        'currency' => 'USD',
                        'insuranceValue' => 200,
                        'showImagesToCustomer' => true,
                        'imageCount' => 1,
                    ],
                ],
            ],
        ]);

        ShipmentAttachment::create([
            'shipment_id' => $shipment->id,
            'uploaded_by' => $customer->id,
            'category' => 'tracking_qr',
            'original_name' => 'qr.svg',
            'stored_name' => 'qr.svg',
            'disk' => 'public',
            'path' => 'shipments/'.$shipment->id.'/qr.svg',
            'mime_type' => 'image/svg+xml',
            'size_bytes' => 512,
            'metadata' => ['asset_type' => 'qr'],
        ]);

        ShipmentAttachment::create([
            'shipment_id' => $shipment->id,
            'uploaded_by' => $customer->id,
            'category' => 'package_image',
            'original_name' => 'visible.webp',
            'stored_name' => 'visible.webp',
            'disk' => 'public',
            'path' => 'shipments/'.$shipment->id.'/visible.webp',
            'mime_type' => 'image/webp',
            'size_bytes' => 1024,
            'metadata' => ['package_id' => 'pkg-1', 'visible_to_customer' => true, 'sort_order' => 1],
        ]);

        ShipmentAttachment::create([
            'shipment_id' => $shipment->id,
            'uploaded_by' => $customer->id,
            'category' => 'package_image',
            'original_name' => 'hidden.webp',
            'stored_name' => 'hidden.webp',
            'disk' => 'public',
            'path' => 'shipments/'.$shipment->id.'/hidden.webp',
            'mime_type' => 'image/webp',
            'size_bytes' => 1024,
            'metadata' => ['package_id' => 'pkg-1', 'visible_to_customer' => false, 'sort_order' => 2],
        ]);

        ShipmentAttachment::create([
            'shipment_id' => $shipment->id,
            'uploaded_by' => $customer->id,
            'category' => 'operations',
            'original_name' => 'internal.pdf',
            'stored_name' => 'internal.pdf',
            'disk' => 'public',
            'path' => 'shipments/'.$shipment->id.'/internal.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4096,
            'metadata' => ['internal' => true],
        ]);

        $response = $this->getJson('/api/track/FDX-2026-ABC12345');

        $response
            ->assertOk()
            ->assertJsonPath('shipment.metadata.shipment_reference', 'SH-TRACK-1001')
            ->assertJsonCount(2, 'shipment.attachments')
            ->assertJsonFragment(['category' => 'tracking_qr'])
            ->assertJsonFragment(['category' => 'package_image'])
            ->assertJsonFragment(['package_id' => 'pkg-1']);
    }

    public function test_admin_can_update_package_metadata_and_delete_shipment(): void
    {
        $admin = User::create([
            'name' => 'Admin Operator',
            'email' => 'shipment-admin@example.test',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'shipment-customer@example.test',
            'password' => 'Password123!',
            'role' => 'customer',
        ]);

        $shipment = Shipment::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'service_level' => 'domestic_express',
            'sender_name' => 'Before Sender',
            'recipient_name' => 'Before Recipient',
            'origin_address' => ['city' => 'Memphis', 'country' => 'US', 'postal_code' => '38116'],
            'destination_address' => ['city' => 'Austin', 'country' => 'US', 'postal_code' => '73301'],
            'weight_kg' => 4.5,
            'declared_value' => 250,
            'quoted_amount' => 80,
            'metadata' => ['shipment_reference' => 'SH-BEFORE-01'],
        ]);

        $token = $admin->createToken('phpunit-admin', [AdminSecurity::TOKEN_ABILITY])->plainTextToken;

        $updateResponse = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/shipments/{$shipment->id}", [
                'sender_name' => 'Updated Sender',
                'recipient_name' => 'Updated Recipient',
                'service_level' => 'freight',
                'weight_kg' => 18.75,
                'declared_value' => 900,
                'metadata' => [
                    'shipment_reference' => 'SH-AFTER-99',
                    'packages' => [
                        [
                            'id' => 'pkg-updated',
                            'packageName' => 'Updated crate',
                            'description' => 'Updated package profile',
                            'category' => 'Industrial',
                            'packageType' => 'Crate',
                            'quantity' => 2,
                            'weight' => 18.75,
                            'length' => 48,
                            'width' => 32,
                            'height' => 28,
                            'declaredValue' => 900,
                            'currency' => 'USD',
                            'insuranceValue' => 400,
                            'showImagesToCustomer' => true,
                            'imageCount' => 0,
                        ],
                    ],
                ],
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('shipment.sender_name', 'Updated Sender')
            ->assertJsonPath('shipment.metadata.shipment_reference', 'SH-AFTER-99')
            ->assertJsonPath('shipment.metadata.packages.0.packageName', 'Updated crate');

        $deleteResponse = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/shipments/{$shipment->id}");

        $deleteResponse->assertOk()->assertJsonPath('message', 'Shipment deleted.');
        $this->assertDatabaseMissing('shipments', ['id' => $shipment->id]);
    }

    public function test_admin_can_resend_tracking_notifications(): void
    {
        $admin = User::create([
            'name' => 'Admin Operator',
            'email' => 'shipment-notify-admin@example.test',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        $customer = User::create([
            'name' => 'Customer Notify User',
            'email' => 'shipment-notify-customer@example.test',
            'password' => 'Password123!',
            'phone' => '+15550123456',
            'role' => 'customer',
        ]);

        $customer->notificationPreference()->create([
            'email' => true,
            'in_app' => true,
            'sms' => true,
        ]);

        $shipment = Shipment::create([
            'user_id' => $customer->id,
            'status' => 'approved',
            'service_level' => 'domestic_express',
            'sender_name' => 'Notify Sender',
            'recipient_name' => 'Notify Recipient',
            'origin_address' => ['city' => 'Memphis', 'country' => 'US', 'postal_code' => '38116'],
            'destination_address' => ['city' => 'Austin', 'country' => 'US', 'postal_code' => '73301', 'email' => 'receiver@example.test', 'phone' => '+15559876543'],
            'weight_kg' => 4.5,
            'declared_value' => 250,
            'quoted_amount' => 80,
            'tracking_number' => 'FDX-2026-RESEND01',
            'metadata' => [
                'receiver' => ['email' => 'receiver@example.test', 'phone' => '+15559876543'],
            ],
        ]);

        $token = $admin->createToken('phpunit-admin', [AdminSecurity::TOKEN_ABILITY])->plainTextToken;

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/shipments/{$shipment->id}/notifications/resend", [
                'channels' => ['in_app', 'email', 'sms'],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Tracking notifications dispatched.')
            ->assertJsonPath('notification_statuses.in_app', 'stored')
            ->assertJsonPath('notification_statuses.sms', 'logged_for_dispatch');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'channel' => 'email',
            'type' => 'shipment_tracking',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'channel' => 'sms',
            'type' => 'shipment_tracking',
        ]);
    }
}
