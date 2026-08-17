@extends('layouts.app')
@section('title', 'Tổng quan · TaskFlow')
@section('heading', 'Chào buổi sáng, '.Str::before(auth()->user()->name, ' ').'! 👋')
@section('subtitle', 'Đây là tình hình công việc của bạn hôm nay.')
@section('actions')
<a class="btn secondary" href="{{ route('projects.create') }}">＋ Dự án mới</a>
<a class="btn primary" href="{{ route('tasks.create') }}">＋ Thêm công việc</a>
@endsection
@section('content')
@php($progress = $taskCount ? round($doneTaskCount / $taskCount * 100) : 0)
<section class="stats">
    <article><span class="stat-icon blue">▣</span><div><small>Tổng dự án</small><strong>{{ $projectCount }}</strong><em><b>↑ {{ $activeProjectCount }}</b> đang hoạt động</em></div></article>
    <article><span class="stat-icon violet">✓</span><div><small>Tổng công việc</small><strong>{{ $taskCount }}</strong><em><b>{{ $doneTaskCount }}</b> đã hoàn thành</em></div></article>
    <article><span class="stat-icon orange">◷</span><div><small>Đang thực hiện</small><strong>{{ max(0, $taskCount - $doneTaskCount) }}</strong><em>Cần bạn tập trung</em></div></article>
    <article><span class="stat-icon red">!</span><div><small>Quá hạn</small><strong>{{ $overdueCount }}</strong><em class="danger">Cần xử lý ngay</em></div></article>
</section>

<div class="dashboard-grid">
    <section class="panel kanban-panel">
        <div class="panel-head"><div><h2>Công việc của tôi</h2><p>Theo dõi tiến độ các công việc gần nhất</p></div><a href="{{ route('tasks.mine') }}">Xem tất cả →</a></div>
        <div class="kanban-board">
            @foreach(['todo' => ['CẦN LÀM','gray'], 'in_progress' => ['ĐANG LÀM','blue'], 'review' => ['ĐÁNH GIÁ','orange']] as $status => [$label,$color])
            <div class="kanban-column"><div class="kanban-title {{ $color }}"><span>{{ $label }}</span><b>{{ $myTasks->where('status',$status)->count() }}</b></div>
                @forelse($myTasks->where('status',$status)->take(3) as $task)
                <a class="kanban-card" href="{{ route('tasks.edit',$task) }}">
                    <span class="mini-project">{{ $task->project->key }}</span><h3>{{ $task->title }}</h3>
                    <div class="kanban-meta"><span class="priority {{ $task->priority }}">● {{ ['low'=>'Thấp','medium'=>'Trung bình','high'=>'Cao','urgent'=>'Khẩn cấp'][$task->priority] ?? $task->priority }}</span><span>◷ {{ $task->due_date?->format('d/m') ?? '—' }}</span></div>
                </a>
                @empty <div class="kanban-empty">Chưa có công việc</div> @endforelse
            </div>
            @endforeach
        </div>
    </section>
    <aside class="dashboard-side">
        <section class="panel compact"><div class="panel-head"><h2>Sắp đến hạn</h2><a href="{{ route('calendar') }}">Xem lịch</a></div>
            @forelse($myTasks->whereNotNull('due_date')->sortBy('due_date')->take(4) as $task)
            <a class="deadline-row" href="{{ route('tasks.edit',$task) }}"><span class="date-box"><b>{{ $task->due_date->format('d') }}</b>{{ $task->due_date->format('M') }}</span><div><strong>{{ Str::limit($task->title,28) }}</strong><small>{{ $task->project->name }}</small></div></a>
            @empty <p class="empty">Không có công việc sắp đến hạn.</p> @endforelse
        </section>
        <section class="panel compact progress-card"><div class="panel-head"><h2>Tiến độ chung</h2><b>{{ $progress }}%</b></div><div class="progress large"><span style="width:{{ $progress }}%"></span></div><p>{{ $doneTaskCount }} trong {{ $taskCount }} công việc đã hoàn thành</p></section>
    </aside>
</div>

<section class="panel project-overview"><div class="panel-head"><div><h2>Tổng quan dự án</h2><p>Cập nhật tiến độ các dự án gần đây</p></div><a href="{{ route('projects.index') }}">Tất cả dự án →</a></div>
    <div class="overview-grid">@forelse($recentProjects as $project) @php($p=$project->tasks_count ? round($project->done_tasks_count/$project->tasks_count*100):0)
    <a href="{{ route('projects.show',$project) }}"><div><span class="project-key">{{ $project->key }}</span><strong>{{ $project->name }}</strong></div><span class="badge {{ $project->status }}">{{ $project->status === 'active' ? 'Đang chạy' : 'Lập kế hoạch' }}</span><div class="progress"><span style="width:{{ $p }}%"></span></div><small>{{ $project->done_tasks_count }}/{{ $project->tasks_count }} công việc · {{ $p }}%</small></a>
    @empty <p class="empty">Chưa có dự án.</p> @endforelse</div>
</section>
@endsection
