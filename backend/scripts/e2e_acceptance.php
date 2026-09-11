<?php

use App\Http\Controllers\PaymentController;
use App\Models\AdminSetting;
use App\Models\PaymentRequest;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use App\Models\User;
use App\Services\TrackingNumberService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function ok(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }

    echo "[ok] {$message}\n";
}

DB::beginTransaction();

try {
    $admin = User::create([
        'name' => 'E2E Admin',
        'email' => 'e2e-admin-'.Str::lower(Str::random(8)).'@example.test',
        'password' => Hash::make('Password123!'),
        'role' => 'admin',
    ]);

    $ledger = User::create([
        'name' => 'Public Shipment Ledger E2E',
        'email' => 'e2e-ledger-'.Str::lower(Str::random(8)).'@example.test',
        'password' => Hash::make(Str::random(32)),
        'role' => 'customer',
    ]);

    $trackingNumber = app(TrackingNumberService::class)->generate('FDX');
    $shipment = Shipment::create([
        'user_id' => $ledger->id,
        'tracking_number' => $trackingNumber,
        'status' => 'booked',
        'service_level' => 'domestic_express',
        'sender_name' => 'E2E Sender',
        'recipient_name' => 'E2E Receiver',
        'origin_address' => ['city' => 'Memphis', 'state' => 'TN', 'country' => 'US', 'postal_code' => '38116', 'email' => 'sender@example.test', 'phone' => '+15550000001'],
        'destination_address' => ['city' => 'Austin', 'state' => 'TX', 'country' => 'US', 'postal_code' => '73301', 'email' => 'receiver@example.test', 'phone' => '+15550000002'],
        'weight_kg' => 8.5,
        'declared_value' => 1200,
        'quoted_amount' => 149.95,
        'estimated_delivery_at' => now()->addDays(3),
        'metadata' => [
            'shipment_reference' => 'E2E-REF-001',
            'admin_created_public_shipment' => true,
            'packages' => [
                ['package_name' => 'E2E Parcel', 'quantity' => 1, 'weight_kg' => 8.5, 'declared_value' => 1200],
            ],
            'logistics' => ['total_shipping_cost' => 149.95],
        ],
    ]);

    $shipment->trackingEvents()->create([
        'status' => 'booked',
        'location' => 'Memphis, TN',
        'description' => 'Shipment created by administrator.',
        'created_by' => $admin->id,
        'occurred_at' => now()->subHour(),
        'latitude' => 35.0424,
        'longitude' => -89.9767,
    ]);
    $shipment->trackingEvents()->create([
        'status' => 'warehouse_processing',
        'location' => 'Little Rock, AR',
        'description' => 'Latest admin update: processed through regional facility.',
        'created_by' => $admin->id,
        'occurred_at' => now(),
        'latitude' => 34.7465,
        'longitude' => -92.2896,
    ]);

    $shipment->routePoints()->createMany([
        ['sort_order' => 1, 'type' => 'origin', 'label' => 'Origin', 'location' => 'Memphis, TN', 'latitude' => 35.0424, 'longitude' => -89.9767, 'country_code' => 'US', 'created_by' => $admin->id],
        ['sort_order' => 50, 'type' => 'checkpoint', 'label' => 'Transit', 'location' => 'Little Rock, AR', 'latitude' => 34.7465, 'longitude' => -92.2896, 'country_code' => 'US', 'created_by' => $admin->id],
        ['sort_order' => 100, 'type' => 'destination', 'label' => 'Destination', 'location' => 'Austin, TX', 'latitude' => 30.2672, 'longitude' => -97.7431, 'country_code' => 'US', 'created_by' => $admin->id],
    ]);

    AdminSetting::updateOrCreate(['key' => 'company_name'], ['value' => 'E2E Logistics', 'type' => 'string', 'group' => 'app', 'label' => 'Company Name']);
    AdminSetting::updateOrCreate(['key' => 'map_default_latitude'], ['value' => '39.8283', 'type' => 'string', 'group' => 'map', 'label' => 'Default Latitude']);
    AdminSetting::updateOrCreate(['key' => 'map_default_longitude'], ['value' => '-98.5795', 'type' => 'string', 'group' => 'map', 'label' => 'Default Longitude']);
    AdminSetting::updateOrCreate(['key' => 'map_zoom'], ['value' => '4', 'type' => 'integer', 'group' => 'map', 'label' => 'Default Zoom']);

    $tracked = Shipment::where('tracking_number', $trackingNumber)->first();
    ok((bool) $tracked, 'System finds only the exact tracking number');
    ok(Shipment::where('tracking_number', $trackingNumber)->count() === 1, 'Tracking number is unique in the database');
    ok($tracked->trackingEvents()->count() === 2, 'Shipment history is based on saved admin updates');
    ok($tracked->routePoints()->whereIn('type', ['origin', 'checkpoint', 'destination'])->count() === 3, 'Origin, transit, and destination route points are saved');

    $trackingHtml = view('pages.tracking', [
        'trackingNumber' => $trackingNumber,
        'shipment' => $tracked->fresh(['payments', 'paymentRequests', 'routePoints', 'trackingEvents', 'driver', 'warehouse', 'attachments']),
        'error' => null,
    ])->render();
    ok(str_contains($trackingHtml, $trackingNumber), 'Public tracking displays authorized shipment information');
    ok(str_contains($trackingHtml, 'Latest admin update'), 'Public tracking displays latest admin update');
    ok(str_contains($trackingHtml, 'leaflet@1.9.4'), 'Default Leaflet map assets load without an API key');
    ok(str_contains($trackingHtml, 'tile.openstreetmap.org'), 'OpenStreetMap tile layer is configured');
    ok(str_contains($trackingHtml, 'L.polyline'), 'Route polyline is rendered from saved coordinates');
    ok(str_contains($trackingHtml, 'Memphis, TN') && str_contains($trackingHtml, 'Little Rock, AR') && str_contains($trackingHtml, 'Austin, TX'), 'Map uses actual saved shipment locations');

    $shipment->update(['status' => 'destination_hub']);
    $shipment->trackingEvents()->create([
        'status' => 'destination_hub',
        'location' => 'Dallas, TX',
        'description' => 'Shipment location updated by administrator.',
        'created_by' => $admin->id,
        'occurred_at' => now()->addMinute(),
        'latitude' => 32.7767,
        'longitude' => -96.7970,
    ]);
    $shipment->routePoints()->create([
        'sort_order' => 75,
        'type' => 'checkpoint',
        'label' => 'Updated Location',
        'location' => 'Dallas, TX',
        'latitude' => 32.7767,
        'longitude' => -96.7970,
        'country_code' => 'US',
        'created_by' => $admin->id,
    ]);
    $updatedHtml = view('pages.tracking', [
        'trackingNumber' => $trackingNumber,
        'shipment' => $shipment->fresh(['payments', 'paymentRequests', 'routePoints', 'trackingEvents', 'driver', 'warehouse', 'attachments']),
        'error' => null,
    ])->render();
    ok(str_contains($updatedHtml, 'Dallas, TX'), 'Guest tracking reflects updated admin location and route point');

    $receiptHtml = view('pages.receipt', ['shipment' => $shipment->fresh(['trackingEvents', 'routePoints'])])->render();
    ok(str_contains($receiptHtml, 'Shipment Receipt') && str_contains($receiptHtml, $trackingNumber), 'Receipt renders printable shipment data');

    $secret = 'e2e-webhook-secret';
    $gateway = PaymentSetting::create([
        'gateway_name' => 'e2e_crypto',
        'api_key' => 'api-key',
        'secret_key' => $secret,
        'public_key' => 'public-key',
        'mode' => 'test',
        'currency' => 'USD',
        'is_active' => true,
    ]);
    $paymentRequest = PaymentRequest::create([
        'shipment_id' => $shipment->id,
        'title' => 'E2E payment request',
        'reason' => 'Acceptance test collection',
        'amount' => 149.95,
        'currency' => 'USD',
        'requested_method' => $gateway->gateway_name,
        'secure_token' => Str::random(64),
        'created_by' => $admin->id,
    ]);
    $transaction = PaymentTransaction::create([
        'payment_request_id' => $paymentRequest->id,
        'provider' => $gateway->gateway_name,
        'provider_reference' => 'E2E-REF-'.Str::upper(Str::random(8)),
        'amount' => 149.95,
        'currency' => 'USD',
        'status' => 'initiated',
    ]);
    $payload = json_encode(['reference' => $transaction->provider_reference, 'status' => 'paid'], JSON_THROW_ON_ERROR);
    $request = Request::create('/api/payments/webhook/'.$gateway->gateway_name, 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_PAYMENT_SIGNATURE' => hash_hmac('sha256', $payload, $secret),
    ], $payload);
    app(PaymentController::class)->webhook($gateway->gateway_name, $request);
    ok($paymentRequest->fresh()->status === 'paid', 'Signed payment callback marks the payment request as paid');

    $unsignedRejected = false;
    try {
        $badRequest = Request::create('/api/payments/webhook/'.$gateway->gateway_name, 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        app(PaymentController::class)->webhook($gateway->gateway_name, $badRequest);
    } catch (Throwable) {
        $unsignedRejected = true;
    }
    ok($unsignedRejected, 'Unsigned payment callback is rejected');

    ok(User::where('role', 'customer')->where('email', 'like', 'e2e-ledger-%')->count() === 1, 'No guest login account is required for public tracking');
    ok($admin->role === 'admin', 'Only an admin role is used for back-office actions');

    DB::rollBack();
    echo "E2E acceptance smoke test completed.\n";
} catch (Throwable $exception) {
    DB::rollBack();
    fwrite(STDERR, '[fail] '.$exception->getMessage()."\n");
    exit(1);
}
