<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('color', 7)->default('#315cf4');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
        Schema::create('label_task', function (Blueprint $table) {
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->primary(['label_id', 'task_id']);
        });
        $now = now();
        DB::table('labels')->insert([
            ['name' => 'Quan trọng', 'color' => '#ef4444', 'description' => 'Các task quan trọng cần ưu tiên', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cao', 'color' => '#f97316', 'description' => 'Ưu tiên cao', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Trung bình', 'color' => '#f59e0b', 'description' => 'Ưu tiên trung bình', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Thấp', 'color' => '#22c55e', 'description' => 'Ưu tiên thấp', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Đang làm', 'color' => '#3b82f6', 'description' => 'Task đang được thực hiện', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Review', 'color' => '#8b5cf6', 'description' => 'Task cần review, kiểm tra', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hoàn thành', 'color' => '#ec4899', 'description' => 'Task đã hoàn thành', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bug', 'color' => '#2563eb', 'description' => 'Các lỗi, sự cố cần xử lý', 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $labelIds = DB::table('labels')->pluck('id', 'name');
        $priorityLabels = ['urgent' => 'Quan trọng', 'high' => 'Cao', 'medium' => 'Trung bình', 'low' => 'Thấp'];
        $statusLabels = ['in_progress' => 'Đang làm', 'review' => 'Review', 'done' => 'Hoàn thành'];
        $links = [];
        foreach (DB::table('tasks')->select('id', 'priority', 'status')->get() as $task) {
            foreach ([$priorityLabels[$task->priority] ?? null, $statusLabels[$task->status] ?? null] as $name) {
                if ($name && isset($labelIds[$name])) {
                    $links[] = ['label_id' => $labelIds[$name], 'task_id' => $task->id];
                }
            }
        }
        if ($links !== []) {
            DB::table('label_task')->insertOrIgnore($links);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('label_task');
        Schema::dropIfExists('labels');
    }
};
