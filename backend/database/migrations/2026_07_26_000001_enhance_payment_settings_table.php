<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->text('webhook_secret')->nullable()->after('public_key');
            $table->string('webhook_url')->nullable()->after('webhook_secret');
            $table->string('merchant_name')->nullable()->after('webhook_url');
            $table->decimal('processing_fee_percent', 5, 2)->default(0)->after('merchant_name');
            $table->decimal('fixed_fee', 10, 2)->default(0)->after('processing_fee_percent');
            $table->decimal('min_amount', 12, 2)->nullable()->after('fixed_fee');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
            $table->json('supported_currencies')->nullable()->after('max_amount');
            $table->json('config')->nullable()->after('supported_currencies');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'webhook_secret', 'webhook_url', 'merchant_name',
                'processing_fee_percent', 'fixed_fee', 'min_amount',
                'max_amount', 'supported_currencies', 'config',
            ]);
        });
    }
};
