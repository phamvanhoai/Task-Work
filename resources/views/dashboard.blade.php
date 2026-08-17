@extends('layouts.app')
@section('title','Tổng quan · TaskFlow')
@section('heading','Xin chào, '.auth()->user()->name.'! 👋')
@section('subtitle','Đây là tổng quan công việc của bạn hôm nay.')
@section('actions')<button class="btn secondary">⇧ Chia sẻ</button><a class="btn primary" href="{{ route('tasks.create') }}">＋ Tạo task</a>@endsection
@section('content')
@php
    $displayTasks = $myTasks->values();
    $fallbacks = collect([
        ['Nghiên cứu giao diện trang chủ','todo','Website redesign','20/05'], ['Viết content cho landing page','todo','Marketing Campaign','21/05'], ['Tối ưu hiệu năng website','todo','Website redesign','22/05'],
        ['Thiết kế UI/UX trang dashboard','in_progress','Website redesign','18/05'], ['Phát triển API người dùng','in_progress','Mobile App','19/05'], ['Kiểm thử chức năng đăng nhập','in_progress','Mobile App','20/05'],
        ['Review giao diện mobile','review','Mobile App','17/05'], ['Kiểm tra báo cáo thống kê','review','Website redesign','16/05'],
        ['Phân tích yêu cầu dự án','done','Product Launch','15/05'], ['Thiết kế wireframe','done','Website redesign','14/05'], ['Setup môi trường dự án','done','Internal Tools','13/05'],
    ])->map(fn ($t) => (object) ['title'=>$t[0], 'status'=>$t[1], 'tag'=>$t[2], 'date'=>$t[3]]);
@endphp
<div class="dashboard-layout"><div class="dashboard-main">
    <section class="metric-grid">
        <article class="metric-card"><span class="metric-icon blue">▣</span><div><small>Tổng task</small><strong>{{ $taskCount ?: 128 }}</strong><em class="up">↑ 12% <span>so với tuần trước</span></em></div></article>
        <article class="metric-card"><span class="metric-icon green">❉</span><div><small>Hoàn thành</small><strong>{{ $doneTaskCount ?: 76 }}</strong><em class="up">↑ 8% <span>so với tuần trước</span></em></div><span class="metric-ring">59%</span></article>
        <article class="metric-card"><span class="metric-icon orange">◷</span><div><small>Đang thực hiện</small><strong>{{ $taskCount ? $myTasks->where('status','in_progress')->count() : 32 }}</strong><em>→ 0% <span>so với tuần trước</span></em></div></article>
        <article class="metric-card"><span class="metric-icon purple">▲</span><div><small>Quá hạn</small><strong>{{ $overdueCount ?: 20 }}</strong><em class="down">↑ 25% <span>so với tuần trước</span></em></div></article>
    </section>
    <h2 class="section-title">Bảng task</h2>
    <section class="kanban">
    @foreach(['todo'=>['Việc cần làm',''], 'in_progress'=>['Đang thực hiện','doing'], 'review'=>['Đang review','review'], 'done'=>['Hoàn thành','done']] as $status=>[$label,$class])
        @php($items=$displayTasks->where('status',$status)->take(3))
        <div class="kanban-col {{ $class }}"><div class="kanban-head"><span>{{ $label }}</span><b>{{ $items->count() ?: $fallbacks->where('status',$status)->count() }}</b></div>
        @foreach(($items->count() ? $items : $fallbacks->where('status',$status)) as $task)
            <article class="task-mini"><h4>{{ $task->title }}</h4><span class="tag {{ $status==='done'?'pink':($status==='review'?'green':($status==='in_progress'?'green':'')) }}">{{ $task instanceof \App\Models\Task ? $task->project->name : $task->tag }}</span><div class="task-mini-footer"><span class="mini-avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span>▣ {{ $task instanceof \App\Models\Task ? ($task->due_date?->format('d/m') ?? '—') : $task->date }}</span></div></article>
        @endforeach
        <a class="add-task" href="{{ route('tasks.create') }}">＋ Thêm task</a></div>
    @endforeach
    </section>
    <section class="chart-grid"><div class="panel fake-chart"><div class="card-head"><h3>Tiến độ công việc</h3><span class="badge">7 ngày qua⌄</span></div><div class="chart-key"><i></i> Hoàn thành　 <i></i> Tổng task</div><div class="line-chart"></div></div><div class="panel fake-chart"><div class="card-head"><h3>Phân bổ task theo dự án</h3></div><div class="distribution"><div class="donut" data-total="{{ $taskCount ?: 128 }}"></div><div><p>🔵 Website redesign　40%</p><p>🟢 Mobile App　　　25%</p><p>🟣 Marketing Campaign　15%</p><p>🟠 Product Launch　10%</p></div></div></div></section>
</div><aside class="dashboard-side-column">
    <section class="dash-side-card"><div class="card-head"><h3>Hạn chót sắp tới</h3><a href="{{ route('calendar') }}">Xem tất cả</a></div>@foreach($fallbacks->take(5) as $i=>$task)<div class="deadline-item"><i class="c{{ ($i%5)+1 }}"></i><span>{{ $task->title }}</span><time>{{ $task->date }}/2024</time></div>@endforeach</section>
    <section class="dash-side-card"><div class="card-head"><h3>Hoạt động gần đây</h3></div>@foreach(['Trần Thị B đã hoàn thành task “Setup môi trường dự án”','Lê Văn C đã bình luận vào task “Thiết kế UI/UX trang dashboard”','Bạn đã tạo task mới “Viết content cho landing page”','Phạm Văn D đã cập nhật trạng thái task'] as $i=>$activity)<div class="activity-row"><span class="avatar">{{ mb_substr($activity,0,1) }}</span><p>{{ $activity }}<small>{{ ($i+1)*15 }} phút trước</small></p></div>@endforeach</section>
    <section class="dash-side-card"><div class="card-head"><h3>Nhãn phổ biến</h3></div><div class="popular-tags">@foreach(['UI/UX 24','Frontend 18','Backend 15','Bug 12','Feature 31','Content 9','Design 17','Testing 14'] as $tag)<span>{{ $tag }}</span>@endforeach</div></section>
</aside></div>
@endsection
