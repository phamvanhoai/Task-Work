@php
    $start = today()->subDays(3);
    $days = collect(range(0, 13))->map(fn ($day) => $start->copy()->addDays($day));
@endphp
<section class="task-gantt"><header class="gantt-header"><div>Task</div><div class="gantt-days">@foreach($days as $day)<span class="{{ $day->isToday()?'today':'' }}"><small>{{ strtoupper($day->translatedFormat('D')) }}</small><b>{{ $day->format('d') }}</b></span>@endforeach</div></header>
@forelse($tasks as $task)
    @php($position = $task->due_date ? max(0,min(13,$start->diffInDays($task->due_date,false))) : null)
    <article class="gantt-row"><a href="{{ route('tasks.edit',$task) }}"><b>{{ Str::limit($task->title,32) }}</b><small>{{ $task->project->name }}</small></a><div class="gantt-track">@if($position!==null)<span class="gantt-bar {{ $task->status }}" style="left:calc({{ $position }} * (100% / 14));width:calc(100% / 14 * 2)">{{ $task->assignee?->name ?? 'Chưa giao' }}</span>@endif</div></article>
@empty<p class="empty">Không có task phù hợp.</p>@endforelse
</section>
