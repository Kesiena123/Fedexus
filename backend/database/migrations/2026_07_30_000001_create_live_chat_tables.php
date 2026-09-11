<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guest_chat_conversations', 'chat_status')) {
            Schema::table('guest_chat_conversations', function (Blueprint $table) {
                $table->string('chat_status', 30)->default('open')->index();
                $table->boolean('is_guest_notified')->default(false);
                $table->boolean('is_admin_notified')->default(false);
                $table->string('subject', 180)->nullable()->change();
            });
        }

        if (! Schema::hasColumn('guest_chat_messages', 'delivery_status')) {
            Schema::table('guest_chat_messages', function (Blueprint $table) {
                $table->string('delivery_status', 20)->default('sent')->index();
                $table->timestamp('delivered_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
            });
        }

        DB::table('guest_chat_conversations')
            ->whereNull('chat_status')
            ->update(['chat_status' => DB::raw('status')]);
    }

    public function down(): void
    {
        Schema::table('guest_chat_conversations', function (Blueprint $table) {
            $dropable = array_intersect(
                ['chat_status', 'is_guest_notified', 'is_admin_notified'],
                Schema::getColumnListing('guest_chat_conversations')
            );
            if (count($dropable)) {
                $table->dropColumn($dropable);
            }
        });

        Schema::table('guest_chat_messages', function (Blueprint $table) {
            $dropable = array_intersect(
                ['delivery_status', 'delivered_at', 'ip_address', 'user_agent'],
                Schema::getColumnListing('guest_chat_messages')
            );
            if (count($dropable)) {
                $table->dropColumn($dropable);
            }
        });
    }
};
