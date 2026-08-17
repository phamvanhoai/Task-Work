@extends('layouts.app')
@section('title','Lịch · TaskFlow') @section('heading','Lịch công việc') @section('subtitle','Theo dõi thời hạn công việc theo tuần.')
@section('actions')<button class="btn secondary">Hôm nay</button><a class="btn primary" href="{{ route('tasks.create') }}"><i data-lucide="plus"></i>Công việc</a>@endsection
@section('content')
@php
    $start = now()->startOfWeek();
    $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));
@endphp
<div class="calendar-toolbar"><button class="icon-btn">‹</button><h2>{{ $start->translatedFormat('F Y') }}</h2><button class="icon-btn">›</button><div class="view-tabs"><a class="active">Tuần</a><a>Tháng</a></div></div>
<div class="calendar-layout"><section class="week-calendar"><div class="week-header"><span>GMT+7</span>@foreach($days as $day)<div class="{{ $day->isToday()?'today':'' }}"><small>{{ strtoupper($day->translatedFormat('D')) }}</small><b>{{ $day->format('d') }}</b></div>@endforeach</div><div class="week-body">@foreach(range(8,18) as $hour)<div class="time-slot"><span>{{ sprintf('%02d:00',$hour) }}</span>@foreach($days as $day)<div class="day-slot">@foreach($tasks->filter(fn($task)=>$task->due_date->isSameDay($day) && $hour === 8 + ($task->id % 10))->take(1) as $task)<a class="calendar-event" href="{{ route('tasks.edit',$task) }}"><b>{{ $task->project->key }}</b>{{ Str::limit($task->title,24) }}</a>@endforeach</div>@endforeach</div>@endforeach</div></section>
<aside><section class="panel"><div class="panel-head"><h2>Sắp đến hạn</h2></div>@forelse($tasks->filter(fn($t)=>$t->due_date->gte(today()))->take(6) as $task)<a class="deadline-row" href="{{ route('tasks.edit',$task) }}"><span class="date-box"><b>{{ $task->due_date->format('d') }}</b>{{ $task->due_date->format('M') }}</span><div><strong>{{ Str::limit($task->title,26) }}</strong><small>{{ $task->project->name }}</small></div></a>@empty<p class="empty">Không có công việc.</p>@endforelse</section></aside></div>
@endsection
