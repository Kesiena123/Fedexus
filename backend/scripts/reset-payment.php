<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Models\PaymentRequest;
use App\Models\PaymentProof;

$pr = PaymentRequest::where('secure_token', 'bBzpmoMDAGUzjEot6vHfYMlQGwdlCknpAFyRV2uFZoU9REV1HkE2xL5XlyKOPnfi')->first();
echo "ID: {$pr->id} Status: {$pr->status}\n";

// Delete proofs and transactions, reset
PaymentProof::where('payment_request_id', $pr->id)->delete();
$pr->transactions()->delete();
$pr->update(['status' => 'payment_required']);
echo "Reset. Status: {$pr->fresh()->status}\n";

// Verify bank accounts exist
$count = \App\Models\BankAccount::count();
echo "Bank accounts: {$count}\n";
foreach (\App\Models\BankAccount::ordered()->get() as $b) {
    echo "  - {$b->bank_name} ({$b->supported_currency}) default=".($b->is_default?'yes':'no')."\n";
}
