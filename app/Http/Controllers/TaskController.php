<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use App\Services\ZaloBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function exportWeekly(Request $request): StreamedResponse
    {
        $tasks = $this->filteredTasks($request)->with(['project', 'assignee', 'reporter'])->orderBy('due_date')->orderBy('id')->get();
        $template = resource_path('templates/Project Weekly Report_GroupName.xlsx');
        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('B2', $request->filled('project_id') ? Project::find($request->integer('project_id'))?->name : 'TaskWork');
        $sheet->setCellValue('B3', today()->startOfWeek()->format('d/m/Y').'-'.today()->endOfWeek()->format('d/m/Y'));
        $sheet->removeRow(4, max(1, $sheet->getHighestRow() - 3));

        $statusLabels = ['todo' => 'Pending', 'in_progress' => 'In Progress', 'review' => 'In Progress', 'done' => 'Completed'];
        $row = 4;
        $row = $this->writeReportSection($sheet, $row, 'I. Status Report', ['#', 'Project Task', 'In-charge', 'Status', 'Notes'], $tasks->map(fn (Task $task, int $index) => [
            $index + 1, $task->title, $task->assignee?->name ?? 'Unassigned', $statusLabels[$task->status], $task->description ?: $task->project->name,
        ])->all(), true);
        $overdue = $tasks->filter(fn (Task $task) => $task->due_date?->isPast() && $task->status !== 'done')->values();
        $row = $this->writeReportSection($sheet, $row, 'II. Project Issues', ['#', 'Project Issue', 'Owner', 'Status', 'Notes (Solution, Suggestion, etc.)'], $overdue->map(fn (Task $task, int $index) => [
            $index + 1, $task->title, $task->reporter?->name ?? '—', $statusLabels[$task->status], 'Overdue: '.$task->due_date->format('d/m/Y'),
        ])->all(), true);
        $nextWeek = $tasks->filter(fn (Task $task) => $task->due_date?->between(today()->addWeek()->startOfWeek(), today()->addWeek()->endOfWeek()))->values();
        $row = $this->writeReportSection($sheet, $row, 'III. Next Week Plan', ['#', 'Project Work Item', 'Deadline', 'In-charge', 'Notes (Task Details, etc.)'], $nextWeek->map(fn (Task $task, int $index) => [
            $index + 1, $task->title, $task->due_date->format('d/m/Y'), $task->assignee?->name ?? 'Unassigned', $task->description ?: $task->project->name,
        ])->all());
        $this->writeReportSection($sheet, $row, 'IV. Other Project Matters/Suggestions', ['#', 'Project Matter/Suggestions', 'Raised By', 'Date', 'Notes'], []);
        $sheet->freezePane('A4');

        $filename = 'Project Weekly Report_'.today()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = ((int) Task::where('status', $data['status'])->max('sort_order')) + 1;
        $task = Task::create($data + ['reporter_id' => auth()->id()]);
        if ($task->assignee && $task->assignee_id !== auth()->id()) {
            $task->assignee->notify(new WorkspaceNotification('Bạn được giao một task mới', $task->title, route('tasks.index', ['search' => $task->title]), 'clipboard-check', 'blue', 'assignments'));
        }
        $this->notifyZaloGroup($task, 'task_created', 'Task mới được tạo');

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
        $this->notifyZaloGroup($task, 'task_updated', 'Task vừa được cập nhật');

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
        $this->notifyZaloGroup($task, 'status_changed', 'Trạng thái task đã thay đổi');

        return response()->json(['message' => 'Đã cập nhật trạng thái.', 'status' => $task->status]);
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->notifyZaloGroup($task, 'task_deleted', 'Task đã bị xóa');
        $task->delete();

        return back()->with('success', 'Đã xóa công việc.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['project_id' => ['required', 'exists:projects,id'], 'title' => ['required', 'string', 'max:200'], 'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['todo', 'in_progress', 'review', 'done'])], 'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'assignee_id' => ['nullable', 'exists:users,id'], 'due_date' => ['nullable', 'date']]);
    }

    private function filteredTasks(Request $request)
    {
        return Task::query()
            ->when($request->search, fn ($query, $value) => $query->where('title', 'like', "%$value%"))
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->when($request->priority, fn ($query, $value) => $query->where('priority', $value))
            ->when($request->project_id, fn ($query, $value) => $query->where('project_id', $value))
            ->when($request->due === 'today', fn ($query) => $query->whereDate('due_date', today()))
            ->when($request->due === 'tomorrow', fn ($query) => $query->whereDate('due_date', today()->addDay()))
            ->when($request->due === 'week', fn ($query) => $query->whereBetween('due_date', [today()->startOfWeek(), today()->endOfWeek()]))
            ->when($request->due === 'none', fn ($query) => $query->whereNull('due_date'))
            ->when($request->boolean('overdue'), fn ($query) => $query->whereDate('due_date', '<', today())->where('status', '!=', 'done'));
    }

    private function writeReportSection($sheet, int $row, string $title, array $headers, array $rows, bool $statusValidation = false): int
    {
        $sheet->setCellValue("A$row", $title);
        $sheet->duplicateStyle($sheet->getStyle('A1'), "A$row:E$row");
        $sheet->getStyle("A$row")->getFont()->setSize(12);
        $row++;
        $sheet->fromArray($headers, null, "A$row");
        $sheet->duplicateStyle($sheet->getStyle('A1'), "A$row:E$row");
        $sheet->getStyle("A$row:E$row")->getFont()->setBold(true)->setItalic(true)->setSize(11);
        $sheet->getStyle("A$row:E$row")->getBorders()->getAllBorders()->setBorderStyle('thin');
        $row++;
        $dataStart = $row;
        foreach ($rows ?: [['', '', '', '', '']] as $values) {
            foreach (['A', 'B', 'C', 'D', 'E'] as $index => $column) {
                $value = $values[$index] ?? '';
                if (is_int($value) || is_float($value)) {
                    $sheet->setCellValue("$column$row", $value);
                } else {
                    $sheet->setCellValueExplicit("$column$row", (string) $value, DataType::TYPE_STRING);
                }
            }
            $sheet->getStyle("A$row:E$row")->getBorders()->getAllBorders()->setBorderStyle('thin');
            $sheet->getStyle("A$row:E$row")->getAlignment()->setVertical('top')->setWrapText(true);
            $sheet->getStyle("A$row")->getAlignment()->setHorizontal('center');
            $row++;
        }
        if ($statusValidation && $row > $dataStart) {
            $validation = new DataValidation;
            $validation->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setFormula1('"Pending, In Progress, Completed"');
            for ($validationRow = $dataStart; $validationRow < $row; $validationRow++) {
                $sheet->getCell("D$validationRow")->setDataValidation(clone $validation);
            }
        }

        return $row + 1;
    }

    private function sendTaskNotification(Task $task, ?int $previousAssignee, string $previousStatus): void
    {
        $task->loadMissing('assignee');
        if ($task->assignee_id && $task->assignee_id !== $previousAssignee && $task->assignee_id !== auth()->id()) {
            $task->assignee->notify(new WorkspaceNotification('Bạn được giao một task', $task->title, route('tasks.index', ['search' => $task->title]), 'user-check', 'violet', 'assignments'));
        } elseif ($task->assignee && $task->status !== $previousStatus && $task->assignee_id !== auth()->id()) {
            $statuses = ['todo' => 'Cần làm', 'in_progress' => 'Đang thực hiện', 'review' => 'Đang review', 'done' => 'Hoàn thành'];
            $task->assignee->notify(new WorkspaceNotification('Trạng thái task đã thay đổi', $task->title.' → '.($statuses[$task->status] ?? $task->status), route('tasks.index', ['search' => $task->title]), 'refresh-cw', $task->status === 'done' ? 'green' : 'orange', 'status_changes'));
        }
    }

    private function notifyZaloGroup(Task $task, string $event, string $title): void
    {
        $task->loadMissing(['project', 'assignee']);
        $statuses = ['todo' => 'Cần làm', 'in_progress' => 'Đang thực hiện', 'review' => 'Đang review', 'done' => 'Hoàn thành'];
        $message = $task->title."\nDự án: ".$task->project->name."\nNgười phụ trách: ".($task->assignee?->name ?? 'Chưa giao')."\nTrạng thái: ".($statuses[$task->status] ?? $task->status);
        app(ZaloBotService::class)->sendGroupNotification($event, $title, $message, route('tasks.index', ['search' => $task->title]));
    }
}
