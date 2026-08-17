<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $tasks = Task::all();

        return view('workspace.reports', [
            'tasks' => $tasks,
            'projects' => Project::withCount(['tasks', 'tasks as done_count' => fn ($query) => $query->where('status', 'done')])->get(),
            'members' => User::withCount(['tasks', 'tasks as done_count' => fn ($query) => $query->where('status', 'done')])->get(),
        ]);
    }

    public function members(): View
    {
        return view('workspace.members', ['members' => User::withCount(['projects', 'tasks', 'tasks as done_count' => fn ($query) => $query->where('status', 'done')])->paginate(8)]);
    }

    public function labels(): View
    {
        $labels = [
            ['name' => 'Quan trọng', 'color' => '#ef4444', 'description' => 'Các task quan trọng cần ưu tiên', 'count' => 24],
            ['name' => 'Cao', 'color' => '#f97316', 'description' => 'Ưu tiên cao', 'count' => 18],
            ['name' => 'Trung bình', 'color' => '#fbbf24', 'description' => 'Ưu tiên trung bình', 'count' => 36],
            ['name' => 'Thấp', 'color' => '#22c55e', 'description' => 'Ưu tiên thấp', 'count' => 22],
            ['name' => 'Đang làm', 'color' => '#3b82f6', 'description' => 'Task đang được thực hiện', 'count' => 42],
            ['name' => 'Review', 'color' => '#8b5cf6', 'description' => 'Task cần review, kiểm tra', 'count' => 17],
            ['name' => 'Hoàn thành', 'color' => '#ec4899', 'description' => 'Task đã hoàn thành', 'count' => 53],
            ['name' => 'Khách hàng', 'color' => '#06b6d4', 'description' => 'Liên quan đến khách hàng', 'count' => 25],
            ['name' => 'Bug', 'color' => '#2563eb', 'description' => 'Các lỗi, sự cố cần xử lý', 'count' => 13],
        ];

        return view('workspace.labels', compact('labels'));
    }

    public function settings(): View
    {
        return view('workspace.settings');
    }
}
