<?php

namespace App\Http\Controllers;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function myTasks(Request $request): View
    {
        $sort = in_array($request->sort, ['newest', 'oldest', 'due_asc', 'due_desc', 'priority'], true) ? $request->sort : 'newest';
        $perPage = in_array((int) $request->per_page, [10, 20, 50], true) ? (int) $request->per_page : 10;
        $baseQuery = Task::where('assignee_id', $request->user()->id)
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->due === 'today', fn ($query) => $query->whereDate('due_date', today()))
            ->when($request->due === 'tomorrow', fn ($query) => $query->whereDate('due_date', today()->addDay()))
            ->when($request->due === 'week', fn ($query) => $query->whereBetween('due_date', [today()->startOfWeek(), today()->endOfWeek()]))
            ->when($request->due === 'none', fn ($query) => $query->whereNull('due_date'));
        $statusCounts = (clone $baseQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $overdueCount = (clone $baseQuery)->whereDate('due_date', '<', today())->where('status', '!=', 'done')->count();
        $tasks = Task::with(['project', 'assignee'])->where('assignee_id', $request->user()->id)
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->due === 'today', fn ($query) => $query->whereDate('due_date', today()))
            ->when($request->due === 'tomorrow', fn ($query) => $query->whereDate('due_date', today()->addDay()))
            ->when($request->due === 'week', fn ($query) => $query->whereBetween('due_date', [today()->startOfWeek(), today()->endOfWeek()]))
            ->when($request->due === 'none', fn ($query) => $query->whereNull('due_date'))
            ->when($request->boolean('overdue'), fn ($query) => $query->whereDate('due_date', '<', today())->where('status', '!=', 'done'));
        match ($sort) {
            'oldest' => $tasks->oldest(),
            'due_asc' => $tasks->orderByRaw('due_date IS NULL')->orderBy('due_date'),
            'due_desc' => $tasks->orderByRaw('due_date IS NULL')->orderByDesc('due_date'),
            'priority' => $tasks->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")->latest(),
            default => $tasks->latest(),
        };
        $tasks = $tasks->paginate($perPage)->withQueryString();

        return view('tasks.mine', [
            'tasks' => $tasks,
            'statusCounts' => $statusCounts,
            'overdueCount' => $overdueCount,
            'projects' => Project::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'sort' => $sort,
            'perPage' => $perPage,
        ]);
    }

    public function calendar(Request $request): View
    {
        $viewMode = in_array($request->view, ['week', 'month', 'agenda'], true) ? $request->view : 'week';
        try {
            $anchor = $request->filled('date') ? Carbon::createFromFormat('Y-m-d', $request->date)->startOfDay() : today();
        } catch (\Throwable) {
            $anchor = today();
        }
        $rangeStart = $viewMode === 'month' ? $anchor->copy()->startOfMonth()->startOfWeek() : $anchor->copy()->startOfWeek();
        $rangeEnd = $viewMode === 'month' ? $anchor->copy()->endOfMonth()->endOfWeek() : ($viewMode === 'agenda' ? $anchor->copy()->addDays(30) : $anchor->copy()->endOfWeek());
        $tasks = Task::with(['project', 'assignee'])->whereBetween('due_date', [$rangeStart, $rangeEnd])
            ->when($request->project_id, fn ($query, $projectId) => $query->where('project_id', $projectId))->orderBy('due_date')->get();
        $upcoming = Task::with('project')->whereDate('due_date', '>=', today())->orderBy('due_date')->limit(6)->get();

        return view('workspace.calendar', [
            'tasks' => $tasks, 'upcoming' => $upcoming, 'anchor' => $anchor, 'viewMode' => $viewMode,
            'projects' => Project::orderBy('name')->get(), 'users' => User::orderBy('name')->get(),
        ]);
    }

    public function reports(): View
    {
        $days = in_array((int) request('days'), [7, 30, 90], true) ? (int) request('days') : 30;
        $periodStart = today()->subDays($days - 1);
        $tasks = Task::whereDate('created_at', '>=', $periodStart)->get();
        $previousTasks = Task::whereBetween('created_at', [$periodStart->copy()->subDays($days), $periodStart->copy()->subSecond()])->get();
        $trend = collect(range(6, 0))->map(function ($offset) use ($tasks) {
            $date = today()->subDays($offset);

            return ['date' => $date->format('d/m'), 'total' => $tasks->where('created_at', '<=', $date->copy()->endOfDay())->count(), 'done' => $tasks->where('completed_at', '<=', $date->copy()->endOfDay())->count()];
        })->push(['date' => today()->format('d/m'), 'total' => $tasks->count(), 'done' => $tasks->where('status', 'done')->count()]);

        return view('workspace.reports', [
            'tasks' => $tasks,
            'projects' => Project::withCount(['tasks', 'tasks as done_count' => fn ($query) => $query->where('status', 'done')])->get(),
            'members' => User::withCount(['tasks', 'tasks as done_count' => fn ($query) => $query->where('status', 'done')])->get(),
            'previousTasks' => $previousTasks,
            'trend' => $trend,
            'days' => $days,
        ]);
    }

    public function members(Request $request): View
    {
        $baseQuery = User::query()->when($request->search, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%")));
        $roleCounts = (clone $baseQuery)->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $members = (clone $baseQuery)->withCount(['projects', 'tasks', 'tasks as done_count' => fn ($query) => $query->where('status', 'done')])
            ->when($request->role, fn ($query, $role) => $query->where('role', $role))->orderBy('name')->paginate(8)->withQueryString();

        return view('workspace.members', ['members' => $members, 'roleCounts' => $roleCounts]);
    }

    public function inviteMember(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'member'])],
            'password' => ['required', 'string', 'min:8'],
        ]);
        User::create($data);

        return back()->with('success', 'Đã thêm thành viên mới.');
    }

    public function updateMember(Request $request, User $member): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($member)],
            'role' => ['required', Rule::in(['admin', 'member'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if ($member->is($request->user())) {
            $data['role'] = $member->role;
        }
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $member->update($data);

        return back()->with('success', 'Đã cập nhật thành viên.');
    }

    public function destroyMember(Request $request, User $member): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        if ($member->is($request->user())) {
            return back()->withErrors(['member' => 'Không thể xóa tài khoản đang đăng nhập.']);
        }
        if ($member->ownedProjects()->exists()) {
            return back()->withErrors(['member' => 'Hãy chuyển quyền sở hữu dự án trước khi xóa thành viên.']);
        }
        $member->delete();

        return back()->with('success', 'Đã xóa thành viên.');
    }

    public function labels(Request $request): View
    {
        $searchQuery = Label::query()->when($request->search, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%$search%")->orWhere('description', 'like', "%$search%")));
        $tabCounts = [
            'all' => (clone $searchQuery)->where('is_archived', false)->count(),
            'system' => (clone $searchQuery)->where('is_system', true)->where('is_archived', false)->count(),
            'mine' => (clone $searchQuery)->where('created_by', $request->user()->id)->where('is_archived', false)->count(),
            'archived' => (clone $searchQuery)->where('is_archived', true)->count(),
        ];
        $type = in_array($request->type, ['all', 'system', 'mine', 'archived'], true) ? $request->type : 'all';
        $labels = (clone $searchQuery)->with('creator')->withCount('tasks')
            ->when($type === 'system', fn ($query) => $query->where('is_system', true)->where('is_archived', false))
            ->when($type === 'mine', fn ($query) => $query->where('created_by', $request->user()->id)->where('is_archived', false))
            ->when($type === 'archived', fn ($query) => $query->where('is_archived', true))
            ->when($type === 'all', fn ($query) => $query->where('is_archived', false))
            ->orderByDesc('tasks_count')->orderBy('name')->paginate(8)->withQueryString();
        $popularLabels = Label::withCount('tasks')->where('is_archived', false)->orderByDesc('tasks_count')->limit(5)->get();

        return view('workspace.labels', compact('labels', 'popularLabels', 'tabCounts', 'type'));
    }

    public function storeLabel(Request $request): RedirectResponse
    {
        $data = $this->validateLabel($request);
        $data['created_by'] = $request->user()->id;
        Label::create($data);

        return back()->with('success', 'Đã tạo nhãn mới.');
    }

    public function updateLabel(Request $request, Label $label): RedirectResponse
    {
        $label->update($this->validateLabel($request));

        return back()->with('success', 'Đã cập nhật nhãn.');
    }

    public function destroyLabel(Label $label): RedirectResponse
    {
        $label->delete();

        return back()->with('success', 'Đã xóa nhãn.');
    }

    private function validateLabel(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);
    }

    public function settings(Request $request): View
    {
        $sessions = collect();
        if (config('session.driver') === 'database') {
            $sessions = \DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get();
        }

        return view('workspace.settings', compact('sessions'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', Rule::in(['Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'UTC'])],
            'locale' => ['required', Rule::in(['vi', 'en'])],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }
        unset($data['avatar']);
        $user->update($data);

        return back()->with('success', 'Đã lưu thông tin cá nhân.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'density' => ['required', Rule::in(['compact', 'standard', 'comfortable'])],
        ]);
        $data += ['show_task_count' => $request->boolean('show_task_count'), 'notification_sound' => $request->boolean('notification_sound'), 'auto_save' => $request->boolean('auto_save'), 'fullscreen_task' => $request->boolean('fullscreen_task')];
        $request->user()->update(['preferences' => $data]);

        return back()->with('success', 'Đã lưu tùy chọn cá nhân.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Đã đổi mật khẩu.');
    }

    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        if (config('session.driver') === 'database') {
            \DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }

        return back()->with('success', 'Đã đăng xuất khỏi các thiết bị khác.');
    }

    public function exportSettings(Request $request)
    {
        $payload = $request->user()->only(['name', 'email', 'phone', 'job_title', 'timezone', 'locale', 'bio', 'preferences', 'created_at']);

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="taskflow-account.json"',
        ]);
    }
}
