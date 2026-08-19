<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', fn (Blueprint $table) => $table->unsignedInteger('sort_order')->default(0)->index());
        foreach (['todo', 'in_progress', 'review', 'done'] as $status) {
            DB::table('tasks')->where('status', $status)->orderBy('created_at')->orderBy('id')->pluck('id')->each(fn ($id, $index) => DB::table('tasks')->where('id', $id)->update(['sort_order' => $index]));
        }
    }

    public function down(): void
    {
        Schema::table('tasks', fn (Blueprint $table) => $table->dropColumn('sort_order'));
    }
};
