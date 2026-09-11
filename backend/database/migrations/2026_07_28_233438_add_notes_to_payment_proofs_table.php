<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('rejection_reason');
            $table->timestamp('resubmission_requested_at')->nullable()->after('verified_at');
            $table->string('resubmission_reason', 2000)->nullable()->after('resubmission_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropColumn(['internal_notes', 'resubmission_requested_at', 'resubmission_reason']);
        });
    }
};
