<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_chat_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('background_color', 20)->default('#ffffff');
            $table->string('text_color', 20)->default('#333333');
            $table->string('accent_color', 20)->default('#667eea');
            $table->string('font_size', 10)->default('14px');
            $table->boolean('save_conversations')->default(true);
            $table->boolean('show_timestamps')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_settings');
    }
};
