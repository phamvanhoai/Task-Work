<div class="reference-pager">
    <span>Hiển thị {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} trong {{ $paginator->total() }} task</span>
    @if($paginator->hasPages())
    <nav class="pager-buttons" aria-label="Phân trang">
        @if($paginator->onFirstPage())<span class="disabled"><i data-lucide="chevron-left"></i></span>@else<a href="{{ $paginator->previousPageUrl() }}" aria-label="Trang trước"><i data-lucide="chevron-left"></i></a>@endif
        @foreach(range(1, $paginator->lastPage()) as $page)<a class="{{ $page === $paginator->currentPage() ? 'active' : '' }}" href="{{ $paginator->url($page) }}" @if($page === $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
        @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" aria-label="Trang sau"><i data-lucide="chevron-right"></i></a>@else<span class="disabled"><i data-lucide="chevron-right"></i></span>@endif
    </nav>
    @else<span></span>@endif
    <form method="get" class="per-page-form">@foreach(request()->except(['page','per_page']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<select name="per_page" aria-label="Số task mỗi trang" data-auto-submit>@foreach([10,20,50] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>@endforeach</select></form>
</div>
