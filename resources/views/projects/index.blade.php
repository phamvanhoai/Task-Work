@extends('layouts.app')
@section('title','Dự án · TaskFlow')
@section('heading')Dự án　<span class="heading-count">{{ $statusCounts->sum() }}</span>@endsection
@section('subtitle','Theo dõi tiến độ và nguồn lực của các dự án.')
@section('actions')@endsection
@section('content')
<div class="project-toolbar">
    <form class="project-search" method="get">@foreach(request()->except(['page','search']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<label><i data-lucide="search"></i><input name="search" value="{{ request('search') }}" placeholder="Tìm kiếm dự án...">@if(request()->filled('search'))<a class="search-reset" href="{{ route('projects.index',request()->except(['page','search'])) }}"><i data-lucide="x"></i></a>@endif</label></form>
    <button class="btn secondary {{ request()->filled('priority')?'active':'' }}" type="button" data-project-filter-toggle><i data-lucide="sliders-horizontal"></i>Bộ lọc</button>
    <form method="get" class="project-sort">@foreach(request()->except(['page','sort']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<select name="sort" data-auto-submit>@foreach(['newest'=>'Mới nhất','oldest'=>'Cũ nhất','due_asc'=>'Hạn gần nhất','progress'=>'Tiến độ cao'] as $value=>$label)<option value="{{ $value }}" @selected($sort===$value)>{{ $label }}</option>@endforeach</select></form>
    <a class="btn primary project-create" href="{{ route('projects.create') }}"><i data-lucide="plus"></i>Dự án mới</a>
</div>
<form class="project-filter-panel {{ request()->filled('priority')?'open':'' }}" method="get">@foreach(request()->except(['page','priority']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<label>Ưu tiên<select name="priority"><option value="">Tất cả ưu tiên</option>@foreach(['urgent'=>'Khẩn cấp','high'=>'Cao','medium'=>'Trung bình','low'=>'Thấp'] as $value=>$label)<option value="{{ $value }}" @selected(request('priority')===$value)>{{ $label }}</option>@endforeach</select></label><button class="btn primary">Áp dụng</button><a class="btn secondary" href="{{ route('projects.index') }}">Đặt lại</a></form>
<nav class="project-status-tabs">
    @foreach([[null,'Tất cả',$statusCounts->sum()],['active','Đang chạy',$statusCounts['active']??0],['planning','Lên kế hoạch',$statusCounts['planning']??0],['on_hold','Tạm dừng',$statusCounts['on_hold']??0],['completed','Hoàn thành',$statusCounts['completed']??0]] as [$status,$label,$count])<a class="{{ !request('overdue')&&request('status')===$status?'active':'' }}" href="{{ route('projects.index',array_merge(request()->except(['page','status','overdue']),$status?['status'=>$status]:[])) }}">{{ $label }} <b>{{ $count }}</b></a>@endforeach
    <a class="overdue {{ request()->boolean('overdue')?'active':'' }}" href="{{ route('projects.index',array_merge(request()->except(['page','status']),['overdue'=>1])) }}">Quá hạn <b>{{ $overdueCount }}</b></a>
</nav>
<div class="project-grid">
@forelse($projects as $project)
    @php($progress=$project->tasks_count ? round($project->done_tasks_count/$project->tasks_count*100):0)
    <article class="project-card"><div class="card-top"><span class="project-key">{{ $project->key }}</span><div class="icon-text"><span class="badge {{ $project->status }}">{{ ['planning'=>'Lên kế hoạch','active'=>'Đang chạy','on_hold'=>'Tạm dừng','completed'=>'Hoàn thành'][$project->status] ?? $project->status }}</span><div class="action-menu"><button class="action-menu-toggle" type="button" aria-label="Thao tác"><i data-lucide="more-vertical"></i></button><div class="action-menu-popover"><a class="project-edit" href="{{ route('projects.edit',$project) }}"><i data-lucide="pencil"></i>Sửa</a><button type="button" data-delete-action="{{ route('projects.destroy',$project) }}" data-delete-message="Xóa dự án và toàn bộ task bên trong? Hành động không thể hoàn tác."><i data-lucide="trash-2"></i>Xóa</button></div></div></div></div>
        <h2><a href="{{ route('projects.show',$project) }}">{{ $project->name }}</a></h2><p>{{ Str::limit($project->description ?: 'Chưa có mô tả cho dự án này.',105) }}</p>
        <div class="project-progress-label"><span>Tiến độ</span><b>{{ $progress }}%</b></div><div class="progress"><span style="width:{{ $progress }}%"></span></div><div class="card-meta"><span>{{ $project->done_tasks_count }} hoàn thành</span><span>{{ $project->tasks_count }} công việc</span></div>
        <footer class="project-card-footer"><div class="project-owner"><span class="avatar">{{ mb_substr($project->owner->name,0,1) }}</span><span><b>{{ $project->owner->name }}</b><small>Chủ dự án</small></span></div><div class="project-members">@foreach($project->members->take(3) as $member)<span class="avatar" title="{{ $member->name }}">{{ mb_substr($member->name,0,1) }}</span>@endforeach @if($project->members->count()>3)<span class="avatar more">+{{ $project->members->count()-3 }}</span>@endif</div><time class="{{ $project->due_date?->isPast()&&$project->status!=='completed'?'text-danger':'' }}"><i data-lucide="calendar-days"></i>{{ $project->due_date?->format('d/m/Y') ?? 'Chưa có hạn' }}</time></footer>
    </article>
@empty<p class="empty">Chưa tìm thấy dự án phù hợp.</p>@endforelse
</div>
@include('projects.partials.pagination',['paginator'=>$projects])
@include('projects.partials.modal')
@endsection
