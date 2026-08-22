@extends('layouts.app')
@section('title','Tổng quan · TaskFlow')
@section('heading','Xin chào, '.auth()->user()->name.'! 👋')
@section('subtitle','Đây là tổng quan công việc của bạn hôm nay.')
@section('actions')<button class="btn secondary"><i data-lucide="share-2"></i> Chia sẻ</button><a class="btn primary" href="{{ route('tasks.create') }}"><i data-lucide="plus"></i> Tạo task</a>@endsection
@section('content')
@php
    $displayTasks = $myTasks->values();
@endphp
<div class="dashboard-layout"><div class="dashboard-main">
    <section class="metric-grid">
        <article class="metric-card"><span class="metric-icon blue"><i data-lucide="list-checks"></i></span><div><small>Tổng task</small><strong>{{ $taskCount }}</strong><em><span>Toàn bộ công việc</span></em></div></article>
        <article class="metric-card"><span class="metric-icon green"><i data-lucide="circle-check-big"></i></span><div><small>Hoàn thành</small><strong>{{ $doneTaskCount }}</strong><em><span>{{ $taskCount?round($doneTaskCount/$taskCount*100):0 }}% tổng task</span></em></div><span class="metric-ring">{{ $taskCount?round($doneTaskCount/$taskCount*100):0 }}%</span></article>
        <article class="metric-card"><span class="metric-icon orange"><i data-lucide="clock-3"></i></span><div><small>Đang thực hiện</small><strong>{{ $statusCounts['in_progress']??0 }}</strong><em><span>Dữ liệu hiện tại</span></em></div></article>
        <article class="metric-card"><span class="metric-icon purple"><i data-lucide="circle-alert"></i></span><div><small>Quá hạn</small><strong>{{ $overdueCount }}</strong><em class="{{ $overdueCount?'down':'' }}"><span>Cần xử lý</span></em></div></article>
    </section>
    <h2 class="section-title">Bảng task</h2>
    <section class="kanban">
    @foreach(['todo'=>['Việc cần làm',''], 'in_progress'=>['Đang thực hiện','doing'], 'review'=>['Đang review','review'], 'done'=>['Hoàn thành','done']] as $status=>[$label,$class])
        @php($items=$displayTasks->where('status',$status)->take(3))
        <div class="kanban-col {{ $class }}"><div class="kanban-head"><span>{{ $label }}</span><b>{{ $items->count() }}</b></div>
        @forelse($items as $task)<article class="task-mini"><h4>{{ $task->title }}</h4><span class="tag {{ $status==='done'?'pink':'green' }}">{{ $task->project->name }}</span><div class="task-mini-footer"><span class="mini-avatar">{{ mb_strtoupper(mb_substr($task->assignee?->name ?? '?', 0, 1)) }}</span><span class="icon-text"><i data-lucide="calendar-days"></i>{{ $task->due_date?->format('d/m')??'—' }}</span></div></article>@empty<p class="kanban-empty">Không có task</p>@endforelse
        <a class="add-task icon-text" href="{{ route('tasks.create') }}"><i data-lucide="plus"></i> Thêm task</a></div>
    @endforeach
    </section>
    <section class="chart-grid"><div class="panel fake-chart"><div class="card-head"><h3>Tiến độ công việc</h3><span class="badge">Hiện tại</span></div><div class="chart-key"><i></i> Hoàn thành　 <i></i> Tổng task</div><div class="dashboard-progress-summary"><strong>{{ $doneTaskCount }}/{{ $taskCount }}</strong><span>task đã hoàn thành</span><i><b style="width:{{ $taskCount?round($doneTaskCount/$taskCount*100):0 }}%"></b></i></div></div><div class="panel fake-chart"><div class="card-head"><h3>Phân bổ task theo dự án</h3></div><div class="distribution"><div class="donut" data-total="{{ $taskCount }}"></div><div>@forelse($projectDistribution as $project)<p><i style="background:#{{ substr(md5($project->key),0,6) }}"></i>{{ $project->name }}　{{ $taskCount?round($project->tasks_count/$taskCount*100):0 }}%</p>@empty<p>Chưa có dữ liệu dự án.</p>@endforelse</div></div></div></section>
</div><aside class="dashboard-side-column">
    <section class="dash-side-card"><div class="card-head"><h3>Hạn chót sắp tới</h3><a href="{{ route('calendar') }}">Xem tất cả</a></div>@forelse($upcomingTasks as $i=>$task)<div class="deadline-item"><i class="c{{ ($i%5)+1 }}"></i><span>{{ $task->title }}</span><time>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</time></div>@empty<p class="empty">Không có deadline sắp tới.</p>@endforelse</section>
    <section class="dash-side-card"><div class="card-head"><h3>Hoạt động gần đây</h3></div>@forelse($recentNotifications as $notification)<div class="activity-row"><span class="avatar">{{ mb_strtoupper(mb_substr($notification->data['title']??'T',0,1)) }}</span><p>{{ $notification->data['title']??'Thông báo' }}<small>{{ $notification->created_at->diffForHumans() }}</small></p></div>@empty<p class="empty">Chưa có hoạt động.</p>@endforelse</section>
    <section class="dash-side-card"><div class="card-head"><h3>Nhãn phổ biến</h3></div><div class="popular-tags">@forelse($popularLabels as $label)<span>{{ $label->name }} {{ $label->tasks_count }}</span>@empty<span>Chưa có nhãn</span>@endforelse</div></section>
</aside></div>
@endsection
