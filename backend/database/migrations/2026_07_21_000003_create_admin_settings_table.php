<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique()->index();
            $table->text('value')->nullable();
            $table->string('type', 32)->default('string')->comment('string, text, boolean, integer, json, email, url');
            $table->string('group', 64)->default('general')->index();
            $table->string('label', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('options')->nullable()->comment('Selectable options for dropdown type');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
