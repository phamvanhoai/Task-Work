<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'TaskWork')</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body><div class="shell"><aside class="sidebar"><a class="brand" href="{{ route('dashboard') }}"><span>TW</span> TaskWork</a>
<nav><a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">⌂ Tổng quan</a><a class="{{ request()->routeIs('projects.*')?'active':'' }}" href="{{ route('projects.index') }}">▦ Projects</a><a class="{{ request()->routeIs('tasks.*')?'active':'' }}" href="{{ route('tasks.index') }}">✓ Công việc</a></nav>
<div class="sidebar-user"><div class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</div><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role }}</small></div><form method="post" action="{{ route('logout') }}">@csrf<button title="Đăng xuất">↪</button></form></div></aside>
<main><header><div><p class="eyebrow">Workspace / @yield('section','Dashboard')</p><h1>@yield('heading','Tổng quan công việc')</h1></div><div class="header-actions">@yield('actions')</div></header>
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error"><strong>Vui lòng kiểm tra lại:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main></div></body></html>
