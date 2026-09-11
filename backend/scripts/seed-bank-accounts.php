<?php

use Illuminate\Contracts\Http\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\BankAccount;

$accounts = [
    [
        'bank_name' => 'Chase Bank',
        'account_name' => 'FreightFlow Logistics LLC',
        'account_number' => '9876543210',
        'swift_bic' => 'CHASUS33',
        'iban' => '',
        'routing_number' => '021000021',
        'branch_name' => 'Manhattan Branch',
        'branch_address' => '383 Madison Avenue, New York, NY 10179',
        'supported_currency' => 'USD',
        'payment_instructions' => 'Please include your payment reference in the transfer description field.',
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ],
    [
        'bank_name' => 'Barclays Bank',
        'account_name' => 'FreightFlow Logistics Ltd',
        'account_number' => '12345678',
        'swift_bic' => 'BARCGB22',
        'iban' => 'GB29NWBK60161331926819',
        'routing_number' => '',
        'branch_name' => 'London City Branch',
        'branch_address' => '1 Churchill Place, London E14 5HP',
        'supported_currency' => 'GBP',
        'payment_instructions' => 'International transfers may take 2-3 business days. Include reference in payment notes.',
        'is_active' => true,
        'is_default' => false,
        'sort_order' => 1,
    ],
    [
        'bank_name' => 'Access Bank',
        'account_name' => 'FreightFlow Logistics Nigeria',
        'account_number' => '0123456789',
        'swift_bic' => 'ABORNGLA',
        'iban' => '',
        'routing_number' => '',
        'branch_name' => 'Victoria Island Branch',
        'branch_address' => '14 Karimu Kotun Street, Victoria Island, Lagos',
        'supported_currency' => 'NGN',
        'payment_instructions' => 'NGN transfers only. Include payment reference to avoid delays in verification.',
        'is_active' => true,
        'is_default' => false,
        'sort_order' => 2,
    ],
];

$count = 0;
foreach ($accounts as $data) {
    $existing = BankAccount::where('account_number', $data['account_number'])->first();
    if (!$existing) {
        BankAccount::create($data);
        $count++;
        echo "Created: {$data['bank_name']} ({$data['supported_currency']})\n";
    } else {
        echo "Skipped (exists): {$data['bank_name']}\n";
    }
}

$total = BankAccount::count();
echo "\nDone. Created {$count} new accounts. Total bank accounts: {$total}\n";
