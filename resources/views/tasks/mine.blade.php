@extends('layouts.app')
@section('title','Task của tôi · TaskFlow')
@section('heading')Task của tôi　<span class="heading-count">{{ $tasks->total() }}</span>@endsection
@section('actions')<a class="btn primary" href="{{ route('tasks.create') }}"><i data-lucide="plus"></i>Tạo task <i data-lucide="chevron-down"></i></a>@endsection
@section('content')
@php($total = $statusCounts->sum())
<div class="mine-tabs">
    @foreach([null=>['Tất cả',$total,'table-2'], 'todo'=>['Việc cần làm',$statusCounts['todo']??0,'clipboard-check'], 'in_progress'=>['Đang thực hiện',$statusCounts['in_progress']??0,'play'], 'review'=>['Đang review',$statusCounts['review']??0,'shield-check'], 'done'=>['Hoàn thành',$statusCounts['done']??0,'check']] as $key=>[$label,$count,$icon])
    <a class="{{ !request('overdue') && (request('status')===$key || ($key===null&&!request('status'))) ? 'active' : '' }}" href="{{ route('tasks.mine', array_merge(request()->except(['page','status','overdue']), $key ? ['status'=>$key] : [])) }}"><i data-lucide="{{ $icon }}"></i>{{ $label }} <b>{{ $count }}</b></a>
    @endforeach
    <a class="overdue-tab {{ request()->boolean('overdue') ? 'active' : '' }}" href="{{ route('tasks.mine', array_merge(request()->except(['page','status']), ['overdue'=>1])) }}"><i data-lucide="circle-alert"></i>Quá hạn <b>{{ $overdueCount }}</b></a>
</div>
<div class="mine-tools"><button class="btn secondary {{ request()->filled('project_id') || request()->filled('priority') || request()->filled('due') ? 'active' : '' }}" type="button" data-filter-toggle><i data-lucide="sliders-horizontal"></i>Bộ lọc</button><form method="get">@foreach(request()->except(['page','sort']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<label class="sort-control">Sắp xếp:<select name="sort" data-auto-submit>@foreach(['newest'=>'Mới nhất','oldest'=>'Cũ nhất','due_asc'=>'Hạn gần nhất','due_desc'=>'Hạn xa nhất','priority'=>'Ưu tiên cao'] as $value=>$label)<option value="{{ $value }}" @selected($sort===$value)>{{ $label }}</option>@endforeach</select></label></form></div>
@include('tasks.partials.filters', ['action' => route('tasks.mine')])
<section class="mine-stats">
@foreach([['Tổng task',$total,'calendar-days','blue'],['Việc cần làm',$statusCounts['todo']??0,'clipboard-check','slate'],['Đang thực hiện',$statusCounts['in_progress']??0,'play','blue'],['Đang review',$statusCounts['review']??0,'clock-3','orange'],['Hoàn thành',$statusCounts['done']??0,'circle-check-big','green'],['Quá hạn',$overdueCount,'circle-alert','red']] as [$label,$count,$icon,$color])
<article><span class="mine-stat-icon {{ $color }}"><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $count }}</strong></div></article>
@endforeach
</section>
<section class="mine-table"><div class="table-wrap"><table><thead><tr><th><input type="checkbox"></th><th>Task</th><th>Dự án</th><th>Ưu tiên</th><th>Hạn chót</th><th>Trạng thái</th><th>Tag</th><th>Thao tác</th></tr></thead><tbody>
@forelse($tasks as $task)<tr><td><input type="checkbox"></td><td><a href="{{ route('tasks.edit',$task) }}"><strong>{{ $task->title }}</strong></a></td><td><span class="project-cell"><i style="background:#{{ substr(md5($task->project->key),0,6) }}"></i>{{ $task->project->name }}</span></td><td><span class="priority {{ $task->priority }}">{{ in_array($task->priority,['urgent','high'])?'↑':($task->priority==='low'?'↓':'−') }}　{{ ['urgent'=>'Khẩn cấp','high'=>'Cao','medium'=>'Trung bình','low'=>'Thấp'][$task->priority] }}</span></td><td class="{{ $task->due_date?->isPast()&&$task->status!=='done'?'text-danger':'' }}"><strong>{{ $task->due_date?->format('d/m/Y') ?? '—' }}</strong></td><td><span class="badge {{ $task->status }}">●　{{ ['todo'=>'Việc cần làm','in_progress'=>'Đang thực hiện','review'=>'Đang review','done'=>'Hoàn thành'][$task->status] }}</span></td><td><span class="task-label">{{ ['urgent'=>'Quan trọng','high'=>'UI/UX','medium'=>'Research','low'=>'Testing'][$task->priority] }}</span></td><td>@include('tasks.partials.actions', ['task' => $task])</td></tr>
@empty<tr><td colspan="8" class="empty">Bạn chưa có task phù hợp với bộ lọc.</td></tr>@endforelse
</tbody></table></div>@include('tasks.partials.pagination', ['paginator'=>$tasks])</section>
@include('tasks.partials.modal')
@endsection
