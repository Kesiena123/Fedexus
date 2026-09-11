<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->enum('role', ['super_admin', 'admin', 'manager', 'support', 'warehouse', 'driver', 'customer'])->default('customer')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_code')->nullable();
            $table->string('password_reset_token')->nullable()->index();
            $table->timestamp('password_reset_expires_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_code_hash')->nullable();
            $table->timestamp('two_factor_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('city');
            $table->string('country', 2);
            $table->json('address');
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number')->nullable()->unique();
            $table->enum('status', ['shipment_requested', 'admin_review', 'approved', 'rejected', 'booked', 'pickup_scheduled', 'picked_up', 'warehouse_processing', 'international_processing', 'destination_hub', 'out_for_delivery', 'delivered', 'paused', 'exception', 'cancelled'])->default('shipment_requested')->index();
            $table->string('service_level')->index();
            $table->string('sender_name');
            $table->string('recipient_name');
            $table->json('origin_address');
            $table->json('destination_address');
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('declared_value', 12, 2)->default(0);
            $table->decimal('quoted_amount', 12, 2)->default(0);
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('location')->nullable();
            $table->text('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stage');
            $table->string('label');
            $table->decimal('percentage', 5, 2)->default(20);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['locked', 'pending', 'checkout_created', 'paid', 'verified', 'failed', 'rejected', 'refunded'])->default('locked')->index();
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['shipment_id', 'stage']);
        });

        Schema::create('shipment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pickup', 'delivery'])->index();
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();
            $table->json('address');
            $table->text('instructions')->nullable();
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'missed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->enum('category', ['delivery', 'billing', 'customs', 'damage', 'account', 'other'])->default('other');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
            $table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open')->index();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['support', 'admin', 'shipment', 'internal'])->default('support')->index();
            $table->string('subject')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->primary(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('sent_at')->index();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('channel')->default('in_app');
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('shipment_schedules');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('users');
    }
};
