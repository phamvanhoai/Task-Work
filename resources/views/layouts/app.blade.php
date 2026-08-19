<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','TaskFlow')</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
@php($userPreferences=auth()->user()->preferences??[])
@php($headerNotifications=auth()->user()->notifications()->latest()->limit(5)->get())
@php($unreadNotificationCount=auth()->user()->unreadNotifications()->count())
<body data-theme="{{ $userPreferences['theme']??'light' }}" class="page-{{ str_replace('.', '-', request()->route()?->getName() ?? 'app') }} density-{{ $userPreferences['density']??'standard' }} {{ ($userPreferences['fullscreen_task']??false)?'fullscreen-tasks':'' }}">
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand-row"><a class="tf-brand" href="{{ route('dashboard') }}"><span class="tf-logo"><i data-lucide="check"></i></span><b>TaskFlow</b></a><button class="sidebar-collapse" type="button" aria-label="Thu gọn sidebar"><i data-lucide="panel-left-close"></i></button></div>
        <nav class="main-nav">
            @foreach([
                ['dashboard','dashboard','house','Tổng quan'], ['tasks.mine','tasks.mine','square-check-big','Task của tôi'], ['tasks.index','tasks.index','table-2','Tất cả task'],
                ['projects.index','projects.*','folder','Dự án'], ['calendar','calendar','calendar-days','Lịch'], ['reports','reports','chart-no-axes-column','Báo cáo'],
                ['members','members','users','Thành viên'], ['labels','labels','tag','Nhãn'], ['settings','settings','settings','Cài đặt'],
            ] as [$route,$match,$icon,$label])
                <a class="{{ request()->routeIs($match)?'active':'' }}" href="{{ route($route) }}"><i data-lucide="{{ $icon }}"></i><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-projects"><div class="side-title">DỰ ÁN YÊU THÍCH <a href="{{ route('projects.create') }}">＋</a></div>@foreach(\App\Models\Project::query()->limit(5)->get() as $sideProject)<a href="{{ route('projects.show',$sideProject) }}"><span class="project-dot c{{ ($loop->index%5)+1 }}"></span>{{ $sideProject->name }}</a>@endforeach</div>
        <div class="upgrade-card"><h3>Nâng cấp trải nghiệm</h3><p>Nâng cấp lên gói Pro để sử dụng đầy đủ tính năng nâng cao.</p><div class="upgrade-art"><span></span><b>🚀</b></div><button>Nâng cấp ngay</button></div>
    </aside>
    <section class="app-stage">
        <header class="topbar"><button class="mobile-menu"><i data-lucide="menu"></i></button><label class="top-search"><i data-lucide="search"></i><input placeholder="Tìm kiếm task, dự án, người dùng..."><kbd>⌘ K</kbd></label><div class="top-actions"><div class="notification-wrap"><button class="notify" type="button" aria-label="Thông báo"><i data-lucide="bell"></i>@if($unreadNotificationCount)<em>{{ $unreadNotificationCount>99?'99+':$unreadNotificationCount }}</em>@endif</button><div class="notification-menu"><div class="notification-menu-head"><b>Thông báo</b>@if($unreadNotificationCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button>Đọc tất cả</button></form>@endif</div><div class="notification-menu-list">@forelse($headerNotifications as $notification)<a class="notification-item {{ $notification->read_at?'':'unread' }}" href="{{ route('notifications.show',$notification) }}"><span class="notification-icon {{ $notification->data['color']??'blue' }}"><i data-lucide="{{ $notification->data['icon']??'bell' }}"></i></span><span><b>{{ $notification->data['title']??'Thông báo' }}</b><small>{{ $notification->data['message']??'' }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span></a>@empty<div class="notification-empty"><i data-lucide="bell-off"></i><span>Chưa có thông báo</span></div>@endforelse</div><a class="notification-view-all" href="{{ route('notifications.index') }}">Xem tất cả thông báo</a></div></div><button><i data-lucide="message-square"></i></button><div class="user-avatar">@if(auth()->user()->avatar_path)<img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="">@else{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}@endif</div><div class="user-meta"><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role==='admin'?'Admin':'Thành viên' }}</small></div><form method="post" action="{{ route('logout') }}">@csrf<button title="Đăng xuất"><i data-lucide="chevron-down"></i></button></form></div></header>
        <main class="page-content"><div class="page-heading"><div><h1>@yield('heading')</h1>@hasSection('subtitle')<p>@yield('subtitle')</p>@endif</div><div class="heading-actions">@yield('actions')</div></div>
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error"><b>Vui lòng kiểm tra lại:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </section>
</div>
<dialog class="confirm-modal" id="confirm-modal"><div class="confirm-card"><span class="confirm-icon"><i data-lucide="triangle-alert"></i></span><div><h2>Xác nhận xóa</h2><p id="confirm-message">Bạn có chắc chắn muốn xóa?</p></div><div class="confirm-actions"><button class="btn secondary" type="button" data-confirm-cancel>Hủy</button><button class="btn danger" type="button" data-confirm-accept><i data-lucide="trash-2"></i>Xóa</button></div></div></dialog>
</body></html>
