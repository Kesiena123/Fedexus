<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name')->unique();
            $table->text('api_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('public_key')->nullable();
            $table->enum('mode', ['test', 'live'])->default('test')->index();
            $table->string('currency', 8)->default('USD')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
