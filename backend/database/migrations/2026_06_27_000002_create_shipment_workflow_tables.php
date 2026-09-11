<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('last_step')->default(1);
            $table->json('payload');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('admin_user_id');
            $table->index('updated_at');
        });

        Schema::create('shipment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('category')->default('additional_document')->index();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_attachments');
        Schema::dropIfExists('shipment_drafts');
    }
};
