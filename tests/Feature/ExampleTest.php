<?php

namespace Tests\Feature;

use App\Models\Label;
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

    public function test_member_can_be_added_from_members_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('members.store'), [
            'name' => 'New Member', 'email' => 'new-member@example.com', 'role' => 'member', 'password' => 'Password123!',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'new-member@example.com', 'role' => 'member']);
    }

    public function test_member_can_be_updated_and_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)->put(route('members.update', $member), [
            'name' => 'Updated Member', 'email' => $member->email, 'role' => 'admin', 'password' => '',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $member->id, 'name' => 'Updated Member', 'role' => 'admin']);

        $this->actingAs($admin)->delete(route('members.destroy', $member))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_label_can_be_created_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('labels.store'), [
            'name' => 'Khách hàng', 'color' => '#06b6d4', 'description' => 'Theo dõi yêu cầu khách hàng',
        ])->assertRedirect();
        $label = Label::where('name', 'Khách hàng')->firstOrFail();

        $this->actingAs($user)->put(route('labels.update', $label), [
            'name' => 'Khách hàng VIP', 'color' => '#2563eb', 'description' => 'Ưu tiên', 'is_archived' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('labels', ['id' => $label->id, 'name' => 'Khách hàng VIP', 'is_archived' => true]);

        $this->actingAs($user)->delete(route('labels.destroy', $label))->assertRedirect();
        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
    }
}
