<?php

use App\Models\Task;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:due-tasks', function () {
    Task::with('assignee')->whereNotNull('assignee_id')->where('status', '!=', 'done')->whereDate('due_date', '<=', today()->addDay())->each(function (Task $task) {
        if (! $task->assignee) {
            return;
        }
        $url = route('tasks.index', ['search' => $task->title]);
        $alreadySent = $task->assignee->notifications()->whereDate('created_at', today())->where('data', 'like', '%'.addcslashes($url, '%_').'%')->exists();
        if (! $alreadySent) {
            $overdue = $task->due_date->isPast() && ! $task->due_date->isToday();
            $task->assignee->notify(new WorkspaceNotification($overdue ? 'Task đã quá hạn' : 'Task sắp đến hạn', $task->title.' · '.$task->due_date->format('d/m/Y'), $url, $overdue ? 'triangle-alert' : 'clock-3', $overdue ? 'orange' : 'blue'));
        }
    });
    $this->info('Đã kiểm tra thông báo deadline.');
})->purpose('Gửi thông báo task sắp đến hạn hoặc quá hạn');

Schedule::command('notifications:due-tasks')->dailyAt('08:00')->withoutOverlapping();
