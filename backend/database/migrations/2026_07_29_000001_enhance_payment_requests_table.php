<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payment_requests', 'description')) {
            return;
        }

        Schema::create('payment_requests_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('category', 80)->nullable();
            $table->string('priority', 10)->default('normal');
            $table->text('reason');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD')->index();
            $table->string('requested_method')->nullable()->index();
            $table->string('status', 30)->default('payment_required')->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->string('secure_token', 96)->unique();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('payment_reference')->nullable()->index();
            $table->unsignedBigInteger('duplicated_from_id')->nullable();
            $table->timestamps();
        });

        $oldRows = DB::table('payment_requests')->get()->map(fn ($r) => (array) $r)->toArray();
        foreach (array_chunk($oldRows, 100) as $chunk) {
            DB::table('payment_requests_new')->insert($chunk);
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_request_id']);
        });
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropForeign(['payment_request_id']);
        });

        Schema::drop('payment_requests');
        Schema::rename('payment_requests_new', 'payment_requests');

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->cascadeOnDelete();
        });
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $dropable = array_intersect(
                ['description', 'category', 'priority', 'internal_notes', 'payment_instructions', 'is_active', 'is_archived', 'duplicated_from_id'],
                Schema::getColumnListing('payment_requests')
            );
            if (count($dropable)) {
                $table->dropColumn($dropable);
            }
        });
    }
};
