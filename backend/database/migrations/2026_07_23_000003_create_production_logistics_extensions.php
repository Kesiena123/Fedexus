<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_route_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->enum('type', ['origin', 'facility', 'checkpoint', 'customs', 'destination'])->default('checkpoint')->index();
            $table->string('label', 160);
            $table->string('location', 255);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shipment_id', 'sort_order']);
        });

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('reason');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD')->index();
            $table->string('requested_method')->nullable()->index();
            $table->enum('status', ['payment_required', 'payment_initiated', 'awaiting_verification', 'paid', 'verified', 'cancelled', 'expired', 'failed'])->default('payment_required')->index();
            $table->string('secure_token', 96)->unique();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 80)->index();
            $table->string('provider_reference')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->enum('status', ['initiated', 'pending', 'paid', 'verified', 'failed', 'cancelled'])->default('initiated')->index();
            $table->json('provider_payload')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guest_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number')->nullable()->index();
            $table->string('guest_name', 140)->nullable();
            $table->string('guest_email', 180)->nullable();
            $table->string('guest_phone', 60)->nullable();
            $table->string('subject', 180)->nullable();
            $table->enum('status', ['open', 'pending', 'closed'])->default('open')->index();
            $table->string('secure_token', 96)->unique();
            $table->timestamp('last_guest_message_at')->nullable();
            $table->timestamp('last_admin_message_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guest_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['guest', 'admin', 'system'])->index();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_chat_messages');
        Schema::dropIfExists('guest_chat_conversations');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('shipment_route_points');
    }
};
