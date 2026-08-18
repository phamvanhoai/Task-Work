<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('timezone', 60)->default('Asia/Ho_Chi_Minh');
            $table->string('locale', 10)->default('vi');
            $table->text('bio')->nullable();
            $table->json('preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['phone', 'job_title', 'timezone', 'locale', 'bio', 'preferences']));
    }
};
