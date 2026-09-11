<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('swift_bic')->nullable();
            $table->string('iban')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('branch_name')->nullable();
            $table->text('branch_address')->nullable();
            $table->string('bank_logo')->nullable();
            $table->string('supported_currency', 8)->default('USD');
            $table->text('payment_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
