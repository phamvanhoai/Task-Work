@php($columns = ['todo'=>['Việc cần làm','slate'], 'in_progress'=>['Đang thực hiện','blue'], 'review'=>['Đang review','orange'], 'done'=>['Hoàn thành','green']])
<section class="task-kanban">
@foreach($columns as $status=>[$label,$color])
    @php($columnTasks = $tasks->getCollection()->where('status', $status))
    <div class="kanban-column {{ $color }}" data-kanban-status="{{ $status }}">
        <header><span class="status-dot"></span><h2>{{ $label }}</h2><b>{{ $columnTasks->count() }}</b></header>
        <div class="kanban-list">
        @forelse($columnTasks as $task)
            <article class="kanban-task" draggable="true" data-task-id="{{ $task->id }}" data-status-url="{{ route('tasks.status',$task) }}"><div class="kanban-task-top"><span class="task-label">{{ ['urgent'=>'Quan trọng','high'=>'UI/UX','medium'=>'Research','low'=>'Testing'][$task->priority] }}</span>@include('tasks.partials.actions', ['task'=>$task])</div><a href="{{ route('tasks.edit',$task) }}"><h3>{{ $task->title }}</h3></a><p>{{ Str::limit($task->description ?: 'Chưa có mô tả.', 75) }}</p><div class="kanban-project"><i style="background:#{{ substr(md5($task->project->key),0,6) }}"></i>{{ $task->project->name }}</div><footer><span class="person"><span class="avatar">{{ mb_substr($task->assignee?->name ?? '?',0,1) }}</span>{{ $task->assignee?->name ?? 'Chưa giao' }}</span><time class="{{ $task->due_date?->isPast()&&$task->status!=='done'?'text-danger':'' }}"><i data-lucide="calendar-days"></i>{{ $task->due_date?->format('d/m') ?? '—' }}</time></footer></article>
        @empty<p class="kanban-empty">Không có task</p>@endforelse
        </div>
    </div>
@endforeach
</section>
