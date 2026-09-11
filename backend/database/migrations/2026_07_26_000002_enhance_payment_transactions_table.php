<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->decimal('fee_amount', 10, 2)->nullable()->after('currency');
            $table->decimal('net_amount', 12, 2)->nullable()->after('fee_amount');
            $table->timestamp('webhook_received_at')->nullable()->after('verified_at');
            $table->json('metadata')->nullable()->after('provider_payload');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['fee_amount', 'net_amount', 'webhook_received_at', 'metadata']);
        });
    }
};
