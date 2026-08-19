<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('zalo_chat_id')->nullable()->unique();
            $table->string('zalo_link_code', 32)->nullable()->unique();
        });
        Schema::create('zalo_chats', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('chat_type', 20);
            $table->string('display_name')->nullable();
            $table->boolean('is_group_target')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zalo_chats');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['zalo_chat_id', 'zalo_link_code']));
    }
};
