<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracking_events', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('country_code', 2)->nullable()->after('longitude');
            $table->string('checkpoint_label')->nullable()->after('country_code');
            $table->string('warehouse_name')->nullable()->after('checkpoint_label');
            $table->text('admin_notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_events', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'country_code',
                'checkpoint_label',
                'warehouse_name',
                'admin_notes',
            ]);
        });
    }
};
