<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use App\Support\AdminSecurity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ShipmentApprovalTrackingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_approval_generates_tracking_number_and_payment_stages(): void
    {
        $admin = User::create([
            'name' => 'Admin Operator',
            'email' => 'admin@example.test',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        $customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.test',
            'password' => 'Password123!',
            'role' => 'customer',
        ]);

        $shipment = Shipment::create([
            'user_id' => $customer->id,
            'status' => 'shipment_requested',
            'service_level' => 'domestic_express',
            'sender_name' => 'Sender Name',
            'recipient_name' => 'Recipient Name',
            'origin_address' => ['city' => 'Memphis', 'country' => 'US', 'postal_code' => '38116'],
            'destination_address' => ['city' => 'Austin', 'country' => 'US', 'postal_code' => '73301'],
            'weight_kg' => 4.5,
            'declared_value' => 250,
            'quoted_amount' => 80,
        ]);

        $token = $admin->createToken('phpunit-admin', [AdminSecurity::TOKEN_ABILITY])->plainTextToken;

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/shipments/{$shipment->id}/approve", [
                'tracking_format' => 'FDX',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('shipment.status', 'approved')
            ->assertJsonPath('tracking_url', fn (string $url) => str_contains($url, '/tracking/FDX-'))
            ->assertJsonPath('notification_statuses.in_app', 'stored');

        $shipment->refresh();

        $this->assertMatchesRegularExpression('/^FDX-\d{4}-[A-Z0-9]{8}$/', (string) $shipment->tracking_number);
        $this->assertCount(5, $shipment->payments()->get());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'channel' => 'in_app',
            'type' => 'shipment_tracking',
        ]);
        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $shipment->id,
            'status' => 'approved',
            'description' => 'Shipment approved by administrator and tracking number generated.',
        ]);
    }
}
