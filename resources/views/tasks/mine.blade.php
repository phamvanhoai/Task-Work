@extends('layouts.app')
@section('title','Công việc của tôi · TaskFlow')
@section('heading','Công việc của tôi')
@section('subtitle','Tập trung vào những việc được giao cho bạn.')
@section('actions')<a class="btn primary" href="{{ route('tasks.create') }}"><i data-lucide="plus"></i>Thêm công việc</a>@endsection
@section('content')
<div class="status-tabs"><a class="{{ request('status') ? '' : 'active' }}" href="{{ route('tasks.mine') }}">Tất cả</a>@foreach(['todo'=>'Cần làm','in_progress'=>'Đang làm','review'=>'Đánh giá','done'=>'Hoàn thành'] as $key=>$label)<a class="{{ request('status')===$key?'active':'' }}" href="{{ route('tasks.mine',['status'=>$key]) }}">{{ $label }}</a>@endforeach</div>
@include('tasks.partials.table', ['tasks' => $tasks])
{{ $tasks->links() }}
@endsection
