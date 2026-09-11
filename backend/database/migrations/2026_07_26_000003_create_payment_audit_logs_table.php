<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_transaction_id')->nullable()->index();
            $table->string('event', 80)->index();
            $table->string('provider', 80)->nullable()->index();
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->enum('previous_status', ['payment_required', 'payment_initiated', 'awaiting_verification', 'initiated', 'pending', 'processing', 'paid', 'verified', 'failed', 'cancelled', 'refunded', 'expired'])->nullable();
            $table->enum('new_status', ['payment_required', 'payment_initiated', 'awaiting_verification', 'initiated', 'pending', 'processing', 'paid', 'verified', 'failed', 'cancelled', 'refunded', 'expired'])->nullable();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audit_logs');
    }
};
