<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->sort, ['newest', 'oldest', 'due_asc', 'progress'], true) ? $request->sort : 'newest';
        $perPage = in_array((int) $request->per_page, [6, 12, 24], true) ? (int) $request->per_page : 6;
        $statisticsQuery = Project::query()
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%$v%")->orWhere('key', 'like', "%$v%")))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v));
        $projects = Project::with(['owner', 'members'])->withCount(['tasks', 'tasks as done_tasks_count' => fn ($q) => $q->where('status', 'done')])
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%$v%")->orWhere('key', 'like', "%$v%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereDate('due_date', '<', today())->where('status', '!=', 'completed'));
        match ($sort) {
            'oldest' => $projects->oldest(),
            'due_asc' => $projects->orderByRaw('due_date IS NULL')->orderBy('due_date'),
            'progress' => $projects->orderByDesc('done_tasks_count'),
            default => $projects->latest(),
        };
        $projects = $projects->paginate($perPage)->withQueryString();

        return view('projects.index', [
            'projects' => $projects, 'users' => User::orderBy('name')->get(),
            'statusCounts' => (clone $statisticsQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'overdueCount' => (clone $statisticsQuery)->whereDate('due_date', '<', today())->where('status', '!=', 'completed')->count(),
            'sort' => $sort, 'perPage' => $perPage,
        ]);
    }

    public function create(): View
    {
        return view('projects.form', ['project' => new Project, 'users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['key'] = strtoupper($data['key']);
        $project = Project::create($data);
        $project->members()->sync($request->input('members', []));
        $recipients = User::whereIn('id', $request->input('members', []))->where('id', '!=', auth()->id())->get();
        Notification::send($recipients, new WorkspaceNotification('Bạn được thêm vào dự án', $project->name, route('projects.show', $project), 'folder-plus', 'violet'));

        return redirect()->route('projects.show', $project)->with('success', 'Đã tạo project.');
    }

    public function show(Project $project): View
    {
        $project->load(['owner', 'members', 'tasks.assignee']);

        return view('projects.show', [
            'project' => $project,
            'projects' => Project::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Project $project): View|JsonResponse
    {
        if ($request->expectsJson()) {
            $project->load('members');

            return response()->json($project);
        }

        return view('projects.form', ['project' => $project, 'users' => User::orderBy('name')->get()]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $existingMemberIds = $project->members()->pluck('users.id');
        $data = $this->validated($request, $project);
        $data['key'] = strtoupper($data['key']);
        $project->update($data);
        $project->members()->sync($request->input('members', []));
        $newMemberIds = collect($request->input('members', []))->diff($existingMemberIds)->reject(fn ($id) => (int) $id === auth()->id());
        Notification::send(User::whereIn('id', $newMemberIds)->get(), new WorkspaceNotification('Bạn được thêm vào dự án', $project->name, route('projects.show', $project), 'folder-plus', 'violet'));

        return redirect()->route('projects.show', $project)->with('success', 'Đã cập nhật project.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Đã xóa project.');
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'], 'key' => ['required', 'alpha_num:ascii', 'max:12', Rule::unique('projects')->ignore($project)],
            'description' => ['nullable', 'string'], 'status' => ['required', Rule::in(['planning', 'active', 'on_hold', 'completed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])], 'owner_id' => ['required', 'exists:users,id'],
            'start_date' => ['nullable', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'members' => ['array'], 'members.*' => ['exists:users,id'],
        ]);
    }
}
