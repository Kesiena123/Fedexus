<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\PaymentSetting;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentStageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $warehouses = [
            ['name' => 'Memphis Global Hub', 'code' => 'MEM', 'city' => 'Memphis', 'country' => 'US', 'address' => ['street' => '2850 Airways Blvd', 'city' => 'Memphis', 'state' => 'TN', 'postal_code' => '38131', 'country' => 'US']],
            ['name' => 'Heathrow Gateway Hub', 'code' => 'LHR', 'city' => 'London', 'country' => 'GB', 'address' => ['street' => 'Heathrow Cargo Area', 'city' => 'London', 'country' => 'GB', 'postal_code' => 'TW6 2GA']],
            ['name' => 'Dubai Transit Center', 'code' => 'DXB', 'city' => 'Dubai', 'country' => 'AE', 'address' => ['street' => 'Dubai Logistics City', 'city' => 'Dubai', 'country' => 'AE']],
            ['name' => 'Lagos Coastal Hub', 'code' => 'LOS', 'city' => 'Lagos', 'country' => 'NG', 'address' => ['street' => 'Apapa Port Complex', 'city' => 'Lagos', 'country' => 'NG']],
            ['name' => 'Sao Paulo Cargo Hub', 'code' => 'GRU', 'city' => 'Sao Paulo', 'country' => 'BR', 'address' => ['street' => 'Guarulhos International Cargo', 'city' => 'Sao Paulo', 'country' => 'BR']],
        ];
        foreach ($warehouses as $w) {
            Warehouse::firstOrCreate(['code' => $w['code']], $w);
        }

        $adminPassword = env('SEED_ADMIN_PASSWORD', 'Password123!');
        $admin = User::create(['name' => 'Admin Operator', 'email' => 'admin@freightflow.test', 'password' => Hash::make($adminPassword), 'role' => 'admin']);
        User::firstOrCreate(
            ['email' => 'driver@freightflow.test'],
            ['name' => 'James Driver', 'password' => Hash::make($adminPassword), 'role' => 'driver']
        );
        $customer = User::create(['name' => 'Maya Carter', 'email' => 'maya@freightflow.test', 'password' => Hash::make($adminPassword), 'role' => 'customer']);

        PaymentSetting::updateOrCreate(['gateway_name' => 'flutterwave'], ['api_key' => env('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK_TEST-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'secret_key' => env('FLUTTERWAVE_SECRET_KEY', 'FLWSEC_TEST-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET', 'whsec_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'mode' => 'test', 'currency' => 'NGN', 'is_active' => true, 'processing_fee_percent' => 1.40, 'fixed_fee' => 0.00]);
        PaymentSetting::updateOrCreate(['gateway_name' => 'stripe'], ['api_key' => env('STRIPE_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'secret_key' => env('STRIPE_SECRET', 'whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'), 'mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'processing_fee_percent' => 2.90, 'fixed_fee' => 0.30]);
        PaymentSetting::updateOrCreate(['gateway_name' => 'paypal'], ['api_key' => env('PAYPAL_CLIENT_ID', 'client_id'), 'secret_key' => env('PAYPAL_CLIENT_SECRET', 'secret'), 'mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'processing_fee_percent' => 3.49, 'fixed_fee' => 0.49]);
        PaymentSetting::updateOrCreate(['gateway_name' => 'bank_transfer'], ['mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'config' => ['bank_name' => 'Test Bank', 'account_name' => 'FreightFlow Inc', 'account_number' => '1234567890', 'routing_number' => '021000021', 'swift_code' => 'TESTUS66', 'payment_instructions' => 'Include reference in description.']]);
        PaymentSetting::updateOrCreate(['gateway_name' => 'crypto'], ['api_key' => env('NOWPAYMENTS_API_KEY', 'np_test_key123'), 'mode' => 'test', 'currency' => 'USD', 'is_active' => true, 'config' => ['provider' => 'nowpayments', 'preferred_coin' => 'btc']]);

        BankAccount::create([
            'bank_name' => 'First National Bank',
            'account_name' => 'FreightFlow Logistics Inc.',
            'account_number' => '1234567890',
            'routing_number' => '021000021',
            'swift_bic' => 'FNBBUS33',
            'iban' => 'US1234567890123456789012',
            'branch_name' => 'Memphis Main Branch',
            'branch_address' => '100 Madison Ave, Memphis, TN 38103',
            'country' => 'US',
            'supported_currency' => 'USD',
            'payment_instructions' => 'Include your payment reference in the transfer description.',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        BankAccount::create([
            'bank_name' => 'Barclays UK',
            'account_name' => 'FreightFlow Logistics Ltd.',
            'account_number' => '98765432',
            'swift_bic' => 'BARCGB22',
            'iban' => 'GB29BARC20000098765432',
            'branch_name' => 'London Corporate Branch',
            'branch_address' => '1 Churchill Place, London E14 5HP',
            'country' => 'GB',
            'supported_currency' => 'GBP',
            'payment_instructions' => 'Quote reference number on all transfers.',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 2,
        ]);

        $shipment = Shipment::create([
            'user_id' => $customer->id,
            'tracking_number' => 'FDX-2026-8K3P91QZ',
            'status' => 'international_processing',
            'service_level' => 'international_priority',
            'sender_name' => 'Maya Carter',
            'recipient_name' => 'Lena Morgan',
            'origin_address' => ['city' => 'Nashville', 'country' => 'US', 'postal_code' => '37201'],
            'destination_address' => ['city' => 'London', 'country' => 'GB', 'postal_code' => 'SW1A 1AA'],
            'weight_kg' => 8.5,
            'declared_value' => 450,
            'quoted_amount' => 142.75,
            'estimated_delivery_at' => now()->addDays(4),
        ]);

        app(PaymentStageService::class)->createStages($shipment);
        $shipment->trackingEvents()->create([
            'status' => 'booked',
            'location' => 'Nashville, TN',
            'description' => 'Shipment booked and deposit captured.',
            'created_by' => $admin->id,
            'occurred_at' => now()->subHours(5),
        ]);
    }
}
