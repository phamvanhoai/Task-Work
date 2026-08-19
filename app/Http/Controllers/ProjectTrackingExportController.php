<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectTrackingExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $tasks = $this->filteredTasks($request)->with(['project', 'assignee'])->orderBy('due_date')->orderBy('id')->get();
        $spreadsheet = IOFactory::load(resource_path('templates/Report3_Project Tracking.xlsx'));
        $statusLabels = ['todo' => 'Pending', 'in_progress' => 'In Progress', 'review' => 'In Review', 'done' => 'Completed'];
        $levelLabels = ['low' => 'Simple', 'medium' => 'Medium', 'high' => 'Complex', 'urgent' => 'Complex'];
        $priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Critical'];

        $wbs = $spreadsheet->getSheetByName('WBS');
        $this->clearRows($wbs, 8, 7);
        foreach ($tasks as $index => $task) {
            $planned = $task->due_date ? 'Week '.$task->due_date->format('W').' ('.$task->due_date->format('d/m/Y').')' : '';
            $this->writeRow($wbs, $index + 8, [$index + 1, $task->project->name, $task->title, $levelLabels[$task->priority], $task->description ?: '', $planned, $statusLabels[$task->status]]);
        }

        $issues = $spreadsheet->getSheetByName('Issues');
        $this->clearRows($issues, 4, 9);
        $overdue = $tasks->filter(fn (Task $task) => $task->due_date?->isPast() && $task->status !== 'done')->values();
        foreach ($overdue as $index => $task) {
            $this->writeRow($issues, $index + 4, [$index + 1, $task->title, $task->description ?: 'Schedule delay', $priorityLabels[$task->priority], $task->assignee?->name ?? 'Unassigned', $task->created_at->format('d/m/Y'), '', 'Open', 'Overdue since '.$task->due_date->format('d/m/Y')]);
        }

        $this->clearRows($spreadsheet->getSheetByName('Defects'), 4, 7);
        $this->clearRows($spreadsheet->getSheetByName('Q&A'), 4, 6);
        $spreadsheet->setActiveSheetIndexByName('WBS');
        $projectKey = $request->filled('project_id') ? Project::find($request->integer('project_id'))?->key : 'All-Projects';
        $filename = 'Report3_Project Tracking_'.($projectKey ?: 'Project').'_'.today()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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

    private function clearRows($sheet, int $firstRow, int $columnCount): void
    {
        for ($row = $firstRow; $row <= $sheet->getHighestRow(); $row++) {
            for ($column = 1; $column <= $columnCount; $column++) {
                $sheet->getCell([$column, $row])->setValue(null);
            }
        }
    }

    private function writeRow($sheet, int $row, array $values): void
    {
        foreach ($values as $index => $value) {
            $cell = $sheet->getCell([$index + 1, $row]);
            if (is_int($value) || is_float($value)) {
                $cell->setValue($value);
            } else {
                $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            }
        }
    }
}
