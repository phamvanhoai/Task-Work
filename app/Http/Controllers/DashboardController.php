<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'projectCount' => Project::count(),
            'activeProjectCount' => Project::where('status', 'active')->count(),
            'taskCount' => Task::count(),
            'doneTaskCount' => Task::where('status', 'done')->count(),
            'overdueCount' => Task::whereNot('status', 'done')->whereDate('due_date', '<', now())->count(),
            'myTasks' => Task::with('project')->where('assignee_id', auth()->id())->orderBy('due_date')->limit(8)->get(),
            'recentProjects' => Project::withCount('tasks')->with('owner')->latest()->limit(5)->get(),
        ]);
    }
}
