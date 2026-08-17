<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Xin chào');
    }

    public function test_authenticated_user_can_view_workspace_screens(): void
    {
        $user = User::factory()->create();

        foreach (['/my-tasks', '/projects', '/tasks', '/calendar', '/reports', '/members', '/labels', '/settings'] as $uri) {
            $this->actingAs($user)->get($uri)->assertOk();
        }
    }

    public function test_task_status_can_be_updated_from_kanban(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Kanban', 'key' => 'KAN', 'owner_id' => $user->id]);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Move me', 'status' => 'todo', 'priority' => 'medium', 'reporter_id' => $user->id]);

        $this->actingAs($user)->patchJson(route('tasks.status', $task), ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('status', 'done');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
        $this->assertNotNull($task->fresh()->completed_at);
    }
}
