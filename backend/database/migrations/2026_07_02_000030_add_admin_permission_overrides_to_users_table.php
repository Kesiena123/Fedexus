<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('granted_admin_permissions')->nullable()->after('suspension_reason');
            $table->json('revoked_admin_permissions')->nullable()->after('granted_admin_permissions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['granted_admin_permissions', 'revoked_admin_permissions']);
        });
    }
};
