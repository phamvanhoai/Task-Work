@extends('layouts.app')
@section('title','Lịch · TaskFlow')
@section('heading','Lịch công việc')
@section('subtitle','Theo dõi thời hạn công việc theo tuần và tháng.')
@section('actions')@endsection
@section('content')
@php
    $weekStart = $anchor->copy()->startOfWeek();
    $days = collect(range(0,6))->map(fn($i) => $weekStart->copy()->addDays($i));
    $previous = $viewMode === 'month' ? $anchor->copy()->subMonth() : $anchor->copy()->subWeek();
    $next = $viewMode === 'month' ? $anchor->copy()->addMonth() : $anchor->copy()->addWeek();
    $monthStart = $anchor->copy()->startOfMonth()->startOfWeek();
    $monthDays = collect(range(0,41))->map(fn($i) => $monthStart->copy()->addDays($i));
@endphp
<div class="calendar-commandbar">
    <div class="calendar-navigation"><a class="icon-btn" href="{{ route('calendar',['view'=>$viewMode,'date'=>$previous->format('Y-m-d')]) }}"><i data-lucide="chevron-left"></i></a><a class="icon-btn" href="{{ route('calendar',['view'=>$viewMode,'date'=>$next->format('Y-m-d')]) }}"><i data-lucide="chevron-right"></i></a><h2>{{ $anchor->translatedFormat('F Y') }}</h2><a class="btn secondary" href="{{ route('calendar',['view'=>$viewMode,'date'=>today()->format('Y-m-d')]) }}">Hôm nay</a></div>
    <nav class="calendar-view-tabs">@foreach(['week'=>'Tuần','month'=>'Tháng','agenda'=>'Danh sách'] as $value=>$label)<a class="{{ $viewMode===$value?'active':'' }}" href="{{ route('calendar',['view'=>$value,'date'=>$anchor->format('Y-m-d')]) }}">{{ $label }}</a>@endforeach</nav>
    <button class="btn secondary" type="button" data-calendar-filter><i data-lucide="sliders-horizontal"></i>Bộ lọc</button><a class="btn primary calendar-create" href="{{ route('tasks.create') }}"><i data-lucide="plus"></i>Tạo công việc</a>
</div>
<form class="calendar-filter-panel {{ request()->filled('project_id')?'open':'' }}" method="get"><input type="hidden" name="view" value="{{ $viewMode }}"><input type="hidden" name="date" value="{{ $anchor->format('Y-m-d') }}"><label>Dự án<select name="project_id"><option value="">Tất cả dự án</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string)request('project_id')===(string)$project->id)>{{ $project->name }}</option>@endforeach</select></label><button class="btn primary">Áp dụng</button><a class="btn secondary" href="{{ route('calendar',['view'=>$viewMode,'date'=>$anchor->format('Y-m-d')]) }}">Đặt lại</a></form>
<div class="calendar-main-layout"><main>
@if($viewMode==='week')
<section class="week-calendar"><div class="week-header"><span>GMT+7</span>@foreach($days as $day)<div class="{{ $day->isToday()?'today':'' }}"><small>{{ strtoupper($day->translatedFormat('D')) }}</small><b>{{ $day->format('d/m') }}</b></div>@endforeach</div><div class="week-body">@foreach(range(8,19) as $hour)<div class="time-slot"><span>{{ sprintf('%02d:00',$hour) }}</span>@foreach($days as $day)<div class="day-slot">@foreach($tasks->filter(fn($task)=>$task->due_date->isSameDay($day) && $hour === 8 + ($task->id % 10))->take(1) as $task)@php($color='#'.substr(md5($task->project->key),0,6))<a class="calendar-event" style="--event-color:{{ $color }}" href="{{ route('tasks.edit',$task) }}"><b>{{ Str::limit($task->title,24) }}</b><small>{{ sprintf('%02d:00',$hour) }} – {{ sprintf('%02d:00',$hour+1) }}</small><span>{{ $task->project->name }}</span></a>@endforeach</div>@endforeach</div>@endforeach</div></section>
@elseif($viewMode==='month')
<section class="month-calendar"><header>@foreach(['T2','T3','T4','T5','T6','T7','CN'] as $label)<span>{{ $label }}</span>@endforeach</header><div class="month-calendar-grid">@foreach($monthDays as $day)<div class="month-day {{ !$day->isSameMonth($anchor)?'muted':'' }} {{ $day->isToday()?'today':'' }}"><b>{{ $day->format('d') }}</b>@foreach($tasks->filter(fn($task)=>$task->due_date->isSameDay($day))->take(3) as $task)<a href="{{ route('tasks.edit',$task) }}"><i style="background:#{{ substr(md5($task->project->key),0,6) }}"></i>{{ Str::limit($task->title,20) }}</a>@endforeach @if($tasks->filter(fn($task)=>$task->due_date->isSameDay($day))->count()>3)<small>+{{ $tasks->filter(fn($task)=>$task->due_date->isSameDay($day))->count()-3 }} công việc</small>@endif</div>@endforeach</div></section>
@else
<section class="calendar-agenda">@forelse($tasks->groupBy(fn($task)=>$task->due_date->format('Y-m-d')) as $date=>$dateTasks)<div class="agenda-day"><header><b>{{ \Carbon\Carbon::parse($date)->translatedFormat('l') }}</b><span>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span></header><div>@foreach($dateTasks as $task)<a href="{{ route('tasks.edit',$task) }}"><i style="background:#{{ substr(md5($task->project->key),0,6) }}"></i><span><b>{{ $task->title }}</b><small>{{ $task->project->name }} · {{ $task->assignee?->name ?? 'Chưa giao' }}</small></span><span class="badge {{ $task->status }}">{{ ['todo'=>'Việc cần làm','in_progress'=>'Đang làm','review'=>'Review','done'=>'Hoàn thành'][$task->status] }}</span></a>@endforeach</div></div>@empty<p class="empty">Không có công việc trong 30 ngày tới.</p>@endforelse</section>
@endif
</main><aside class="calendar-sidebar">
    <section class="mini-calendar"><header><h3>{{ $anchor->translatedFormat('F Y') }}</h3><div><a href="{{ route('calendar',['view'=>$viewMode,'date'=>$anchor->copy()->subMonth()->format('Y-m-d')]) }}"><i data-lucide="chevron-left"></i></a><a href="{{ route('calendar',['view'=>$viewMode,'date'=>$anchor->copy()->addMonth()->format('Y-m-d')]) }}"><i data-lucide="chevron-right"></i></a></div></header><div class="mini-week">@foreach(['T2','T3','T4','T5','T6','T7','CN'] as $label)<b>{{ $label }}</b>@endforeach</div><div class="mini-days">@foreach($monthDays as $day)<a class="{{ !$day->isSameMonth($anchor)?'muted':'' }} {{ $day->isToday()?'today':'' }}" href="{{ route('calendar',['view'=>'week','date'=>$day->format('Y-m-d')]) }}">{{ $day->format('d') }}</a>@endforeach</div></section>
    <section class="upcoming-card"><header><h3>Sắp đến hạn</h3><a href="{{ route('tasks.index',['sort'=>'due_asc']) }}">Xem tất cả</a></header>@forelse($upcoming as $task)<a class="upcoming-row" href="{{ route('tasks.edit',$task) }}"><i style="background:#{{ substr(md5($task->project->key),0,6) }}"></i><span><b>{{ Str::limit($task->title,27) }}</b><small>{{ $task->project->name }}</small></span><time>{{ $task->due_date->isToday()?'Hôm nay':$task->due_date->format('d/m') }}</time></a>@empty<p class="empty">Không có công việc.</p>@endforelse</section>
    <section class="calendar-stats"><h3>Thống kê tuần</h3><div><article><i data-lucide="calendar-days"></i><span>Sự kiện<b>{{ $tasks->count() }}</b></span></article><article><i data-lucide="check"></i><span>Hoàn thành<b>{{ $tasks->where('status','done')->count() }}</b></span></article><article><i data-lucide="clock-3"></i><span>Sắp diễn ra<b>{{ $tasks->where('status','!=','done')->count() }}</b></span></article></div></section>
</aside></div>
@include('tasks.partials.modal')
@endsection
