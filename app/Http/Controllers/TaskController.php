<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        if ($viewMode === 'kanban') {
            $tasks->orderBy('sort_order')->orderBy('id');
        } else {
            match ($sort) {
                'oldest' => $tasks->oldest(),
                'due_asc' => $tasks->orderByRaw('due_date IS NULL')->orderBy('due_date'),
                'due_desc' => $tasks->orderByRaw('due_date IS NULL')->orderByDesc('due_date'),
                'priority' => $tasks->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")->latest(),
                default => $tasks->latest(),
            };
        }
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
        $data = $this->validated($request);
        $data['sort_order'] = ((int) Task::where('status', $data['status'])->max('sort_order')) + 1;
        $task = Task::create($data + ['reporter_id' => auth()->id()]);
        if ($task->assignee && $task->assignee_id !== auth()->id()) {
            $task->assignee->notify(new WorkspaceNotification('Bạn được giao một task mới', $task->title, route('tasks.index', ['search' => $task->title]), 'clipboard-check', 'blue'));
        }

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
        $previousAssignee = $task->assignee_id;
        $previousStatus = $task->status;
        $data = $this->validated($request);
        if ($data['status'] !== $previousStatus) {
            $data['sort_order'] = ((int) Task::where('status', $data['status'])->max('sort_order')) + 1;
        }
        $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;
        $task->update($data);
        $this->sendTaskNotification($task, $previousAssignee, $previousStatus);

        return redirect()->route('tasks.index')->with('success', 'Đã cập nhật công việc.');
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $previousStatus = $task->status;
        $data = $request->validate(['status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'done'])], 'position' => ['nullable', 'integer', 'min:0']]);
        DB::transaction(function () use ($task, $data) {
            $task->update(['status' => $data['status'], 'completed_at' => $data['status'] === 'done' ? ($task->completed_at ?? now()) : null]);
            $orderedIds = Task::where('status', $data['status'])->whereKeyNot($task->id)->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
            $position = min((int) ($data['position'] ?? count($orderedIds)), count($orderedIds));
            array_splice($orderedIds, $position, 0, [$task->id]);
            foreach ($orderedIds as $index => $id) {
                Task::whereKey($id)->update(['sort_order' => $index]);
            }
        });
        $this->sendTaskNotification($task, $task->assignee_id, $previousStatus);

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

    private function sendTaskNotification(Task $task, ?int $previousAssignee, string $previousStatus): void
    {
        $task->loadMissing('assignee');
        if ($task->assignee_id && $task->assignee_id !== $previousAssignee && $task->assignee_id !== auth()->id()) {
            $task->assignee->notify(new WorkspaceNotification('Bạn được giao một task', $task->title, route('tasks.index', ['search' => $task->title]), 'user-check', 'violet'));
        } elseif ($task->assignee && $task->status !== $previousStatus && $task->assignee_id !== auth()->id()) {
            $statuses = ['todo' => 'Cần làm', 'in_progress' => 'Đang thực hiện', 'review' => 'Đang review', 'done' => 'Hoàn thành'];
            $task->assignee->notify(new WorkspaceNotification('Trạng thái task đã thay đổi', $task->title.' → '.($statuses[$task->status] ?? $task->status), route('tasks.index', ['search' => $task->title]), 'refresh-cw', $task->status === 'done' ? 'green' : 'orange'));
        }
    }
}
