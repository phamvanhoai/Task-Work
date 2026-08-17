<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@taskwork.local'], ['name' => 'Quản trị viên', 'password' => 'ChangeMe123!', 'role' => 'admin']);
        $dev = User::firstOrCreate(['email' => 'dev@taskwork.local'], ['name' => 'Nguyễn Developer', 'password' => 'ChangeMe123!', 'role' => 'member']);
        $project = Project::firstOrCreate(['key' => 'TASK'], ['name' => 'TaskWork Platform', 'description' => 'Xây dựng nền tảng quản lý project và công việc cho team IT.', 'status' => 'active', 'priority' => 'high', 'owner_id' => $admin->id, 'start_date' => now(), 'due_date' => now()->addMonth()]);
        $project->members()->syncWithoutDetaching([$admin->id => ['role' => 'owner'], $dev->id => ['role' => 'member']]);
        Task::firstOrCreate(['project_id' => $project->id, 'title' => 'Hoàn thiện dashboard MVP'], ['status' => 'in_progress', 'priority' => 'high', 'assignee_id' => $dev->id, 'reporter_id' => $admin->id, 'due_date' => now()->addDays(5)]);
    }
}
