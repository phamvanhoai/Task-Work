<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\ZaloChat;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_tasks_can_be_reordered_inside_a_kanban_column(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Ordering', 'key' => 'ORD', 'owner_id' => $user->id]);
        $first = Task::create(['project_id' => $project->id, 'title' => 'First', 'status' => 'todo', 'priority' => 'medium', 'reporter_id' => $user->id, 'sort_order' => 0]);
        $second = Task::create(['project_id' => $project->id, 'title' => 'Second', 'status' => 'todo', 'priority' => 'medium', 'reporter_id' => $user->id, 'sort_order' => 1]);

        $this->actingAs($user)->patchJson(route('tasks.status', $second), ['status' => 'todo', 'position' => 0])->assertOk();

        $this->assertSame([$second->id, $first->id], Task::where('status', 'todo')->orderBy('sort_order')->pluck('id')->all());
    }

    public function test_task_can_be_placed_at_a_specific_position_in_another_kanban_column(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Moving', 'key' => 'MOV', 'owner_id' => $user->id]);
        $existing = Task::create(['project_id' => $project->id, 'title' => 'Existing', 'status' => 'review', 'priority' => 'medium', 'reporter_id' => $user->id, 'sort_order' => 0]);
        $moving = Task::create(['project_id' => $project->id, 'title' => 'Moving', 'status' => 'todo', 'priority' => 'medium', 'reporter_id' => $user->id, 'sort_order' => 0]);

        $this->actingAs($user)->patchJson(route('tasks.status', $moving), ['status' => 'review', 'position' => 0])->assertOk();

        $this->assertSame('review', $moving->fresh()->status);
        $this->assertSame([$moving->id, $existing->id], Task::where('status', 'review')->orderBy('sort_order')->pluck('id')->all());
    }

    public function test_tasks_can_be_exported_as_the_project_tracking_excel_template(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Weekly Export', 'key' => 'WEX', 'owner_id' => $user->id]);
        Task::create(['project_id' => $project->id, 'title' => 'Exported task', 'status' => 'in_progress', 'priority' => 'high', 'reporter_id' => $user->id, 'assignee_id' => $user->id, 'due_date' => today()->addDays(2)]);

        $response = $this->actingAs($user)->get(route('tasks.export.project-tracking', ['project_id' => $project->id]));

        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);
        $path = tempnam(sys_get_temp_dir(), 'project-tracking-');
        file_put_contents($path, $content);
        $spreadsheet = IOFactory::load($path);
        unlink($path);

        $this->assertSame(['WBS', 'Issues', 'Defects', 'Q&A'], $spreadsheet->getSheetNames());
        $this->assertSame('Exported task', $spreadsheet->getSheetByName('WBS')->getCell('C8')->getValue());
    }

    public function test_member_can_be_added_from_members_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('members.store'), [
            'name' => 'New Member', 'email' => 'new-member@example.com', 'role' => 'member', 'password' => 'Password123!',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'new-member@example.com', 'role' => 'member']);
    }

    public function test_members_are_exported_to_real_excel_columns(): void
    {
        $admin = User::factory()->create(['name' => 'Pham Van Hoai', 'email' => 'hoai@example.com', 'phone' => '0333622144', 'role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('members.export'));
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = tempnam(sys_get_temp_dir(), 'members-');
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::load($path)->getActiveSheet();
        unlink($path);

        $this->assertSame('Họ và tên', $sheet->getCell('B1')->getValue());
        $this->assertSame('Pham Van Hoai', $sheet->getCell('B2')->getValue());
        $this->assertSame('0333622144', $sheet->getCell('D2')->getValue());
    }

    public function test_regular_member_cannot_manage_other_members(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $target = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->delete(route('members.destroy', $target))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
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

    public function test_system_label_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $label = Label::where('is_system', true)->firstOrFail();

        $this->actingAs($user)->delete(route('labels.destroy', $label))->assertRedirect();

        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
    }

    public function test_profile_preferences_and_password_can_be_updated(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123!']);
        $this->actingAs($user)->put(route('settings.profile'), [
            'name' => 'Nguyễn Văn A', 'email' => 'nguyenvana@example.com', 'phone' => '0123456789',
            'job_title' => 'Project Manager', 'timezone' => 'Asia/Ho_Chi_Minh', 'locale' => 'vi', 'bio' => 'Quản lý dự án.',
        ])->assertRedirect();
        $this->actingAs($user)->put(route('settings.preferences'), [
            'theme' => 'light', 'density' => 'standard', 'show_task_count' => '1', 'auto_save' => '1',
        ])->assertRedirect();
        $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => 'OldPassword123!', 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Nguyễn Văn A', $user->name);
        $this->assertTrue($user->preferences['show_task_count']);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->actingAs($user)->get(route('settings'))->assertOk()->assertSee('data-theme="light"', false);
        $this->actingAs($user)->get(route('settings.export'))->assertOk()->assertHeader('content-type', 'application/json');
    }

    public function test_task_assignment_creates_and_manages_notification(): void
    {
        $reporter = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::create(['name' => 'Notify', 'key' => 'NTF', 'owner_id' => $reporter->id]);

        $this->actingAs($reporter)->post(route('tasks.store'), [
            'project_id' => $project->id, 'title' => 'Notification task', 'status' => 'todo', 'priority' => 'high', 'assignee_id' => $assignee->id,
        ])->assertRedirect();
        $notification = $assignee->notifications()->firstOrFail();
        $this->assertSame('Bạn được giao một task mới', $notification->data['title']);

        $this->actingAs($assignee)->get(route('notifications.index'))->assertOk()->assertSee('Notification task');
        $this->actingAs($assignee)->get(route('notifications.show', $notification))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($assignee)->delete(route('notifications.destroy', $notification))->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_access_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $owner->notify(new WorkspaceNotification('Private', 'Only owner', route('dashboard')));

        $this->actingAs($stranger)->get(route('notifications.show', $owner->notifications()->first()))->assertForbidden();
    }

    public function test_zalo_webhook_links_a_private_chat_with_a_secure_code(): void
    {
        config(['services.zalo_bot.token' => 'test-token', 'services.zalo_bot.webhook_secret' => 'test-secret']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => '1']])]);
        $user = User::factory()->create(['zalo_link_code' => 'LINK123ABC']);
        $payload = ['ok' => true, 'result' => ['event_name' => 'message.text.received', 'message' => [
            'from' => ['id' => 'zalo-user', 'display_name' => 'Zalo User', 'is_bot' => false],
            'chat' => ['id' => 'private-chat-1', 'chat_type' => 'PRIVATE'],
            'text' => '/link LINK123ABC',
        ]]];

        $this->postJson(route('webhooks.zalo-bot'), $payload)->assertForbidden();
        $this->withHeader('X-Bot-Api-Secret-Token', 'test-secret')->postJson(route('webhooks.zalo-bot'), $payload)->assertOk();

        $this->assertSame('private-chat-1', $user->fresh()->zalo_chat_id);
        $this->assertDatabaseHas('zalo_chats', ['chat_id' => 'private-chat-1', 'chat_type' => 'PRIVATE']);
    }

    public function test_workspace_notification_is_sent_to_private_zalo_chat(): void
    {
        config(['services.zalo_bot.token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => '1']])]);
        $user = User::factory()->create(['zalo_chat_id' => 'private-chat']);

        $user->notify(new WorkspaceNotification('Task mới', 'Kiểm tra Zalo', route('dashboard')));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === 'private-chat');
    }

    public function test_every_new_task_is_broadcast_to_the_zalo_group_even_without_an_assignee(): void
    {
        config(['services.zalo_bot.token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => '1']])]);
        $reporter = User::factory()->create();
        $project = Project::create(['name' => 'Group Broadcast', 'key' => 'ZGB', 'owner_id' => $reporter->id]);
        ZaloChat::create(['chat_id' => 'group-chat', 'chat_type' => 'GROUP', 'is_group_target' => true]);

        $this->actingAs($reporter)->post(route('tasks.store'), [
            'project_id' => $project->id, 'title' => 'Task chung', 'status' => 'todo', 'priority' => 'medium', 'assignee_id' => '',
        ])->assertRedirect();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === 'group-chat' && str_contains($request['text'], 'Task chung'));
    }

    public function test_upcoming_unassigned_deadline_is_broadcast_once_to_the_zalo_group(): void
    {
        config(['services.zalo_bot.token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => '1']])]);
        $owner = User::factory()->create();
        $project = Project::create(['name' => 'Deadline', 'key' => 'DDL', 'owner_id' => $owner->id]);
        Task::create(['project_id' => $project->id, 'title' => 'Sắp hết hạn', 'status' => 'todo', 'priority' => 'high', 'reporter_id' => $owner->id, 'due_date' => today()->addDay()]);
        ZaloChat::create(['chat_id' => 'deadline-group', 'chat_type' => 'GROUP', 'is_group_target' => true]);

        Artisan::call('notifications:due-tasks');
        Artisan::call('notifications:due-tasks');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === 'deadline-group' && str_contains($request['text'], 'ngày mai'));
    }

    public function test_notification_preferences_control_personal_and_group_channels(): void
    {
        config(['services.zalo_bot.token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true])]);
        $user = User::factory()->create(['role' => 'admin', 'zalo_chat_id' => 'private-chat']);
        $group = ZaloChat::create(['chat_id' => 'group-chat', 'chat_type' => 'GROUP', 'is_group_target' => true]);

        $this->actingAs($user)->put(route('settings.notifications'), [
            'in_app' => '1', 'assignments' => '1', 'deadlines' => '1',
        ])->assertRedirect();
        $this->actingAs($user)->put(route('settings.zalo.group'), [
            'chat_id' => $group->chat_id, 'notification_options' => '1', 'deadlines' => '1',
        ])->assertRedirect();

        $this->assertFalse($user->fresh()->notification_preferences['zalo_personal']);
        $this->assertFalse($group->fresh()->notification_preferences['task_created']);
        $user->notify(new WorkspaceNotification('Phân công', 'Không gửi Zalo', route('dashboard'), category: 'assignments'));
        Http::assertNothingSent();
        $this->assertCount(1, $user->notifications);
    }
}
