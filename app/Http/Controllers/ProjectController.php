<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::with('owner')->withCount(['tasks', 'tasks as done_tasks_count' => fn ($q) => $q->where('status', 'done')])
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%$v%")->orWhere('key', 'like', "%$v%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(12)->withQueryString();

        return view('projects.index', ['projects' => $projects, 'users' => User::orderBy('name')->get()]);
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

        return redirect()->route('projects.show', $project)->with('success', 'Đã tạo project.');
    }

    public function show(Project $project): View
    {
        $project->load(['owner', 'members', 'tasks.assignee']);

        return view('projects.show', compact('project'));
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
        $data = $this->validated($request, $project);
        $data['key'] = strtoupper($data['key']);
        $project->update($data);
        $project->members()->sync($request->input('members', []));

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
