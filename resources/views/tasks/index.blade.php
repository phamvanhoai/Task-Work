@extends('layouts.app')
@section('title','Tất cả công việc · TaskFlow')
@section('heading','Tất cả công việc')
@section('subtitle','Quản lý và theo dõi toàn bộ công việc của nhóm.')
@section('actions')<a class="btn primary" href="{{ route('tasks.create') }}">＋ Thêm công việc</a>@endsection
@section('content')
<div class="view-tabs"><a class="active" href="{{ route('tasks.index') }}">▤ Danh sách</a><a href="{{ route('calendar') }}">▦ Lịch</a></div>
<form class="filters"><label class="search-field">⌕<input name="search" value="{{ request('search') }}" placeholder="Tìm kiếm công việc..."></label><select name="project_id"><option value="">Tất cả dự án</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(request('project_id')==$project->id)>{{ $project->name }}</option>@endforeach</select><select name="status"><option value="">Tất cả trạng thái</option>@foreach(['todo'=>'Cần làm','in_progress'=>'Đang làm','review'=>'Đánh giá','done'=>'Hoàn thành'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select><button class="btn secondary">Lọc</button></form>
@include('tasks.partials.table', ['tasks' => $tasks])
{{ $tasks->links() }}
@endsection
