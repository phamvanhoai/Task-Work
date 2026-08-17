<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $sort = in_array($request->sort, ['newest', 'oldest', 'due_asc', 'due_desc', 'priority'], true) ? $request->sort : 'newest';
        $viewMode = in_array($request->view, ['list', 'kanban', 'gantt'], true) ? $request->view : 'list';
        $perPage = in_array((int) $request->per_page, [10, 20, 50], true) ? (int) $request->per_page : 10;
        if ($viewMode !== 'list') {
            $perPage = 50;
        }
        $statisticsQuery = Task::query()
            ->when($request->search, fn ($q, $v) => $q->where('title', 'like', "%$v%"))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($request->project_id, fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->due === 'today', fn ($q) => $q->whereDate('due_date', today()))
            ->when($request->due === 'tomorrow', fn ($q) => $q->whereDate('due_date', today()->addDay()))
            ->when($request->due === 'week', fn ($q) => $q->whereBetween('due_date', [today()->startOfWeek(), today()->endOfWeek()]))
            ->when($request->due === 'none', fn ($q) => $q->whereNull('due_date'));
        $tasks = Task::with(['project', 'assignee'])
            ->when($request->search, fn ($q, $v) => $q->where('title', 'like', "%$v%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($request->project_id, fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->due === 'today', fn ($q) => $q->whereDate('due_date', today()))
            ->when($request->due === 'tomorrow', fn ($q) => $q->whereDate('due_date', today()->addDay()))
            ->when($request->due === 'week', fn ($q) => $q->whereBetween('due_date', [today()->startOfWeek(), today()->endOfWeek()]))
            ->when($request->due === 'none', fn ($q) => $q->whereNull('due_date'))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereDate('due_date', '<', today())->where('status', '!=', 'done'));
        match ($sort) {
            'oldest' => $tasks->oldest(),
            'due_asc' => $tasks->orderByRaw('due_date IS NULL')->orderBy('due_date'),
            'due_desc' => $tasks->orderByRaw('due_date IS NULL')->orderByDesc('due_date'),
            'priority' => $tasks->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")->latest(),
            default => $tasks->latest(),
        };
        $tasks = $tasks->paginate($perPage)->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'projects' => Project::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'statusCounts' => (clone $statisticsQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'overdueCount' => (clone $statisticsQuery)->whereDate('due_date', '<', today())->where('status', '!=', 'done')->count(),
            'sort' => $sort,
            'perPage' => $perPage,
            'viewMode' => $viewMode,
        ]);
    }

    public function create(Request $request): View
    {
        return view('tasks.form', ['task' => new Task(['project_id' => $request->project_id]), 'projects' => Project::orderBy('name')->get(), 'users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $task = Task::create($this->validated($request) + ['reporter_id' => auth()->id()]);

        return redirect()->route('tasks.index')->with('success', 'Đã tạo công việc.');
    }

    public function edit(Request $request, Task $task): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($task);
        }

        return view('tasks.form', ['task' => $task, 'projects' => Project::orderBy('name')->get(), 'users' => User::orderBy('name')->get()]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $this->validated($request);
        $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;
        $task->update($data);

        return redirect()->route('tasks.index')->with('success', 'Đã cập nhật công việc.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'done'])]]);
        $task->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        return response()->json(['message' => 'Đã cập nhật trạng thái.', 'status' => $task->status]);
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('success', 'Đã xóa công việc.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['project_id' => ['required', 'exists:projects,id'], 'title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'done'])], 'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assignee_id' => ['nullable', 'exists:users,id'], 'due_date' => ['nullable', 'date']]);
    }
}
