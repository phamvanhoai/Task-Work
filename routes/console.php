<?php

use App\Models\Task;
use App\Notifications\WorkspaceNotification;
use App\Services\ZaloBotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:due-tasks', function () {
    Task::with(['assignee', 'project'])->where('status', '!=', 'done')->whereDate('due_date', '<=', today()->addDay())->each(function (Task $task) {
        $url = route('tasks.index', ['search' => $task->title]);
        $overdue = $task->due_date->isPast() && ! $task->due_date->isToday();
        $title = $overdue ? 'Task đã quá hạn' : ($task->due_date->isToday() ? 'Task đến hạn hôm nay' : 'Task sắp đến hạn ngày mai');
        if ($task->assignee) {
            $alreadySent = $task->assignee->notifications()->whereDate('created_at', today())->where('data', 'like', '%'.addcslashes($url, '%_').'%')->exists();
            if (! $alreadySent) {
                $task->assignee->notify(new WorkspaceNotification($title, $task->title.' · '.$task->due_date->format('d/m/Y'), $url, $overdue ? 'triangle-alert' : 'clock-3', $overdue ? 'orange' : 'blue'));
            }
        }
        if (Cache::add('zalo-deadline-'.$task->id.'-'.today()->format('Y-m-d'), true, now()->endOfDay())) {
            $message = $task->title."\nDự án: ".$task->project->name."\nNgười phụ trách: ".($task->assignee?->name ?? 'Chưa giao')."\nDeadline: ".$task->due_date->format('d/m/Y');
            app(ZaloBotService::class)->sendGroupNotification($title, $message, $url);
        }
    });
    $this->info('Đã kiểm tra thông báo deadline.');
})->purpose('Gửi thông báo task sắp đến hạn hoặc quá hạn');

Schedule::command('notifications:due-tasks')->dailyAt('08:00')->withoutOverlapping();
