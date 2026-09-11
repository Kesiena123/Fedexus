<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminShipmentEditTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $customer;

    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Operator',
            'email' => 'edit-admin@example.test',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'edit-customer@example.test',
            'password' => 'Password123!',
            'role' => 'customer',
        ]);

        $this->shipment = Shipment::create([
            'user_id' => $this->customer->id,
            'status' => 'approved',
            'service_level' => 'domestic_express',
            'sender_name' => 'Original Sender',
            'recipient_name' => 'Original Recipient',
            'origin_address' => [
                'city' => 'Memphis',
                'country' => 'US',
                'state' => 'TN',
                'address' => '123 Main St',
                'postal_code' => '38116',
            ],
            'destination_address' => [
                'city' => 'Austin',
                'country' => 'US',
                'state' => 'TX',
                'address' => '456 Elm St',
                'postal_code' => '73301',
            ],
            'weight_kg' => 10.5,
            'declared_value' => 500,
            'quoted_amount' => 120.50,
            'tracking_number' => 'FDX-2026-TESTEDIT01',
            'metadata' => [
                'sender' => ['company' => 'Old Corp', 'phone' => '+1111111111', 'email' => 'old@test.com'],
                'receiver' => ['company' => 'Old Inc', 'phone' => '+1222222222', 'email' => 'oldrec@test.com'],
                'description' => 'Original description',
                'priority' => 'normal',
                'cost' => [
                    'shipping_cost' => 100,
                    'handling_fee' => 10,
                    'insurance' => 5,
                    'customs_fee' => 0,
                    'tax' => 5.50,
                    'discount' => 0,
                    'additional_charges' => 0,
                ],
                'packages' => [
                    [
                        'package_type' => 'Box',
                        'weight_kg' => 10.5,
                        'length_cm' => 30,
                        'width_cm' => 20,
                        'height_cm' => 15,
                        'quantity' => 1,
                        'description' => 'Original package',
                    ],
                ],
            ],
        ]);
    }

    public function test_guest_cannot_access_edit_page(): void
    {
        $response = $this->get(route('admin.shipments.edit', $this->shipment));
        $response->assertRedirect('/login');
    }

    public function test_edit_page_renders_with_all_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.shipments.edit', $this->shipment));

        $response->assertOk();
        $response->assertSee('Edit Shipment');
        $response->assertSee($this->shipment->tracking_number);
        $response->assertSee('Sender Information');
        $response->assertSee('Receiver Information');
        $response->assertSee('Shipment Information');
        $response->assertSee('Parcel Information');
        $response->assertSee('Cost Breakdown');
        $response->assertSee('Location & Route', false);
        $response->assertSee('Shipment Photos');

        $response->assertSee('Original Sender');
        $response->assertSee('Original Recipient');
        $response->assertSee('domestic_express');
        $response->assertSee('Old Corp');
        $response->assertSee('Old Inc');
        $response->assertSee('Original description');
        $response->assertSee('Memphis');
        $response->assertSee('Austin');

        $response->assertSee('Save Changes');
        $response->assertSee('Cancel');
    }

    public function test_post_updates_shipment_basic_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.shipments.edit', $this->shipment))
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Updated Sender',
                'sender_company' => 'New Corp',
                'sender_phone' => '+1333333333',
                'sender_email' => 'sender@new.com',
                'sender_country' => 'US',
                'sender_state' => 'CA',
                'sender_city' => 'Los Angeles',
                'sender_address' => '789 Sunset Blvd',
                'sender_postal' => '90001',

                'recipient_name' => 'Updated Recipient',
                'recipient_company' => 'New Inc',
                'recipient_phone' => '+1444444444',
                'recipient_email' => 'recipient@new.com',
                'recipient_country' => 'US',
                'recipient_state' => 'NY',
                'recipient_city' => 'New York',
                'recipient_address' => '321 Broadway',
                'recipient_postal' => '10001',

                'service_level' => 'international_priority',
                'shipment_description' => 'Updated description for shipment',
                'estimated_delivery_at' => '2026-08-15',
                'priority' => 'high',
                'status' => 'international_processing',

                'package_type' => 'Pallet',
                'weight_kg' => 50.75,
                'length_cm' => 120,
                'width_cm' => 80,
                'height_cm' => 60,
                'quantity' => 2,
                'package_description' => 'Industrial equipment pallet',

                'shipping_cost' => 200,
                'handling_fee' => 25,
                'insurance' => 50,
                'customs_fee' => 30,
                'tax' => 15.75,
                'discount' => 20,
                'additional_charges' => 10,

                'origin_latitude' => 34.0522,
                'origin_longitude' => -118.2437,
                'destination_latitude' => 40.7128,
                'destination_longitude' => -74.0060,
            ]);

        $response->assertRedirect(route('admin.shipments.edit', $this->shipment));
        $response->assertSessionHas('success', 'Shipment updated successfully.');

        $this->shipment->refresh();

        $this->assertEquals('Updated Sender', $this->shipment->sender_name);
        $this->assertEquals('Updated Recipient', $this->shipment->recipient_name);
        $this->assertEquals('international_priority', $this->shipment->service_level);
        $this->assertEquals(50.75, (float) $this->shipment->weight_kg);
        $this->assertEquals('international_processing', $this->shipment->status);

        $expectedAmount = 200 + 25 + 50 + 30 + 15.75 + 10 - 20;
        $this->assertEquals($expectedAmount, (float) $this->shipment->quoted_amount);

        $origin = $this->shipment->origin_address;
        $this->assertEquals('Los Angeles', $origin['city']);
        $this->assertEquals('CA', $origin['state']);
        $this->assertEquals('US', $origin['country']);
        $this->assertEquals('789 Sunset Blvd', $origin['address']);
        $this->assertEquals('90001', $origin['postal_code']);

        $dest = $this->shipment->destination_address;
        $this->assertEquals('New York', $dest['city']);
        $this->assertEquals('NY', $dest['state']);
        $this->assertEquals('US', $dest['country']);
        $this->assertEquals('321 Broadway', $dest['address']);
        $this->assertEquals('10001', $dest['postal_code']);

        $metadata = $this->shipment->metadata;
        $this->assertEquals('New Corp', $metadata['sender']['company']);
        $this->assertEquals('New Inc', $metadata['receiver']['company']);
        $this->assertEquals('Updated description for shipment', $metadata['description']);
        $this->assertEquals('high', $metadata['priority']);
        $this->assertEquals(200, $metadata['cost']['shipping_cost']);
        $this->assertEquals('Pallet', $metadata['packages'][0]['package_type']);
        $this->assertEquals(50.75, $metadata['packages'][0]['weight_kg']);
    }

    public function test_post_creates_route_points_when_coordinates_provided(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Test',
                'recipient_name' => 'Test',
                'service_level' => 'domestic_express',
                'status' => 'approved',
                'origin_latitude' => 34.0522,
                'origin_longitude' => -118.2437,
                'destination_latitude' => 40.7128,
                'destination_longitude' => -74.0060,
                'sender_city' => 'Los Angeles',
                'sender_country' => 'US',
                'recipient_city' => 'New York',
                'recipient_country' => 'US',
            ]);

        $this->assertDatabaseHas('shipment_route_points', [
            'shipment_id' => $this->shipment->id,
            'type' => 'origin',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
        ]);

        $this->assertDatabaseHas('shipment_route_points', [
            'shipment_id' => $this->shipment->id,
            'type' => 'destination',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);
    }

    public function test_post_creates_tracking_event(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Test',
                'recipient_name' => 'Test',
                'service_level' => 'freight',
                'status' => 'international_processing',
                'sender_city' => 'Chicago',
            ]);

        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'status' => 'international_processing',
            'location' => 'Chicago',
            'description' => 'Shipment details updated by administrator.',
        ]);
    }

    public function test_validation_fails_on_missing_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.shipments.edit', $this->shipment))
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => '',
                'recipient_name' => '',
                'service_level' => '',
                'status' => '',
            ]);

        $response->assertRedirect(route('admin.shipments.edit', $this->shipment));
        $response->assertSessionHasErrors(['sender_name', 'recipient_name', 'service_level', 'status']);
    }

    public function test_validation_fails_on_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.shipments.edit', $this->shipment))
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Test',
                'recipient_name' => 'Test',
                'service_level' => 'domestic_express',
                'status' => 'nonexistent_status_xyz',
            ]);

        $response->assertStatus(422);
    }

    public function test_partial_update_preserves_unchanged_fields(): void
    {
        $originalOrigin = $this->shipment->origin_address;
        $originalDest = $this->shipment->destination_address;
        $originalMeta = $this->shipment->metadata;

        $this->actingAs($this->admin)
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Only Name Changed',
                'recipient_name' => $this->shipment->recipient_name,
                'service_level' => $this->shipment->service_level,
                'status' => $this->shipment->status,
                'weight_kg' => $this->shipment->weight_kg,
            ]);

        $this->shipment->refresh();

        $this->assertEquals('Only Name Changed', $this->shipment->sender_name);
        $this->assertEquals($originalOrigin['city'], $this->shipment->origin_address['city']);
        $this->assertEquals($originalDest['city'], $this->shipment->destination_address['city']);
        $this->assertEquals($originalMeta['description'], $this->shipment->metadata['description']);
    }

    public function test_tracking_event_contains_foreach_loop_update_description(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Final Sender',
                'recipient_name' => 'Final Recipient',
                'service_level' => 'freight',
                'status' => 'delivered',
                'sender_city' => 'Seattle',
            ]);

        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'description' => 'Shipment details updated by administrator.',
        ]);
    }

    public function test_edit_page_shows_existing_attachments(): void
    {
        $attachment = ShipmentAttachment::create([
            'shipment_id' => $this->shipment->id,
            'uploaded_by' => $this->admin->id,
            'category' => 'package_image',
            'original_name' => 'test-photo.jpg',
            'stored_name' => 'test-photo.jpg',
            'disk' => 'public',
            'path' => 'shipments/'.$this->shipment->id.'/test-photo.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'metadata' => [],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.shipments.edit', $this->shipment));

        $response->assertOk();
        $response->assertSee('test-photo.jpg');
    }

    public function test_total_amount_calculation_is_correct(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Calc Test',
                'recipient_name' => 'Calc Test',
                'service_level' => 'domestic_express',
                'status' => 'approved',
                'shipping_cost' => 100,
                'handling_fee' => 20,
                'insurance' => 15,
                'customs_fee' => 10,
                'tax' => 8.50,
                'discount' => 25,
                'additional_charges' => 5,
            ]);

        $this->shipment->refresh();

        $expected = 100 + 20 + 15 + 10 + 8.50 + 5 - 25;
        $this->assertEquals($expected, (float) $this->shipment->quoted_amount);
    }

    public function test_estimated_delivery_date_is_stored(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shipments.edit', $this->shipment), [
                'sender_name' => 'Date Test',
                'recipient_name' => 'Date Test',
                'service_level' => 'domestic_express',
                'status' => 'approved',
                'estimated_delivery_at' => '2026-12-25',
            ]);

        $this->shipment->refresh();
        $this->assertNotNull($this->shipment->estimated_delivery_at);
        $this->assertEquals('2026-12-25', $this->shipment->estimated_delivery_at->format('Y-m-d'));
    }
}
