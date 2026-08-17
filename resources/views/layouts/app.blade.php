<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','TaskFlow')</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="page-{{ str_replace('.', '-', request()->route()?->getName() ?? 'app') }}"><div class="app-shell">
<aside class="app-sidebar"><a class="tf-brand" href="{{ route('dashboard') }}"><span class="tf-logo">✓</span><b>TaskFlow</b></a><nav class="main-nav">
@foreach([
    ['dashboard','dashboard','⌂','Tổng quan'], ['tasks.mine','tasks.mine','✓','Task của tôi'], ['tasks.index','tasks.*','▦','Tất cả task'],
    ['projects.index','projects.*','□','Dự án'], ['calendar','calendar','▣','Lịch'], ['reports','reports','⌁','Báo cáo'],
    ['members','members','♧','Thành viên'], ['labels','labels','◇','Nhãn'], ['settings','settings','⚙','Cài đặt'],
] as [$route,$match,$icon,$label])<a class="{{ request()->routeIs($match)?'active':'' }}" href="{{ route($route) }}"><i>{{ $icon }}</i><span>{{ $label }}</span></a>@endforeach
</nav><div class="sidebar-projects"><div class="side-title">DỰ ÁN YÊU THÍCH <b>＋</b></div>@foreach(\App\Models\Project::query()->limit(5)->get() as $sideProject)<a href="{{ route('projects.show',$sideProject) }}"><span class="project-dot c{{ ($loop->index%5)+1 }}"></span>{{ $sideProject->name }}</a>@endforeach</div><div class="upgrade-card"><h3>Nâng cấp trải nghiệm</h3><p>Nâng cấp lên gói Pro để sử dụng đầy đủ tính năng nâng cao.</p><div class="rocket">🚀</div><button>Nâng cấp ngay</button></div></aside>
<section class="app-stage"><div class="topbar"><button class="mobile-menu">☰</button><div class="top-search"><span>⌕</span><input placeholder="Tìm kiếm task, dự án, người dùng..."><kbd>⌘ K</kbd></div><div class="top-actions"><button class="notify">♧<em>3</em></button><button>▢</button><div class="user-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</div><div class="user-meta"><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role==='admin'?'Admin':'Thành viên' }}</small></div><form method="post" action="{{ route('logout') }}">@csrf<button title="Đăng xuất">⌄</button></form></div></div>
<main class="page-content"><div class="page-heading"><div><h1>@yield('heading')</h1>@hasSection('subtitle')<p>@yield('subtitle')</p>@endif</div><div class="heading-actions">@yield('actions')</div></div>
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error"><b>Vui lòng kiểm tra lại:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main></section></div></body></html>
