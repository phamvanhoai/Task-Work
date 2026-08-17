@php($hasFilters = request()->filled('project_id') || request()->filled('priority'))
<form class="task-filter-form {{ $hasFilters ? 'open' : '' }}" method="get" action="{{ $action }}">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    @if(request('overdue'))<input type="hidden" name="overdue" value="1">@endif
    @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
    <div class="filter-panel">
        <label>Dự án<select name="project_id"><option value="">Tất cả dự án</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></label>
        <label>Ưu tiên<select name="priority"><option value="">Tất cả ưu tiên</option>@foreach(['urgent'=>'Khẩn cấp','high'=>'Cao','medium'=>'Trung bình','low'=>'Thấp'] as $value=>$label)<option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>@endforeach</select></label>
        <button class="btn primary" type="submit"><i data-lucide="check"></i>Áp dụng</button>
        <a class="btn secondary" href="{{ $action }}">Đặt lại</a>
    </div>
</form>
