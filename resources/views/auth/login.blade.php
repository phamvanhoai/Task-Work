<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng nhập · TaskFlow</title>@vite(['resources/css/app.css'])</head>
<body class="login-screen"><div class="login-wrap">
<section class="login-form-side"><div class="login-inner">
    <a class="tf-brand login-logo" href="/"><span class="tf-logo">✓</span><b>TaskFlow</b></a>
    <h1>Chào mừng trở lại! 👋</h1><p>Đăng nhập để tiếp tục quản lý công việc của bạn</p>
    <form method="post" action="{{ route('login.store') }}">@csrf
        <label>Email hoặc tên đăng nhập<div class="input-icon"><span>♙</span><input type="email" name="email" value="{{ old('email') }}" placeholder="Nhập email hoặc tên đăng nhập" required autofocus></div></label>
        <label>Mật khẩu<div class="input-icon"><span>♙</span><input type="password" name="password" placeholder="Nhập mật khẩu" required><b>◉</b></div></label>
        <div class="login-options"><label><input type="checkbox" name="remember" checked> Ghi nhớ đăng nhập</label><a href="#">Quên mật khẩu?</a></div>
        @error('email')<div class="field-error">{{ $message }}</div>@enderror
        <button class="login-submit">Đăng nhập</button>
    </form>
    <div class="divider"><span>hoặc</span></div><button class="google-btn"><b>G</b> Đăng nhập với Google</button><p class="signup">Chưa có tài khoản?　<a href="#">Đăng ký ngay</a></p>
</div></section>
<section class="login-visual"><div class="visual-dots"></div><div class="visual-copy"><h2>Quản lý công việc hiệu quả</h2><p>Tạo, phân công và theo dõi tiến độ công việc<br>mọi lúc, mọi nơi.</p></div>
    <div class="login-dashboard">
        <aside><i></i><i>⌂</i><i>▣</i><i>□</i><i>▦</i><i>⌁</i><i>♧</i><i>⚙</i></aside>
        <main><h3>Tổng quan</h3><div class="ld-stats"><article><small>Tổng task</small><b>128</b><em>↑ 12%</em></article><article><small>Hoàn thành</small><b>76</b><em>↑ 8%</em></article><article><small>Đang tiến hành</small><b>34</b><em>↑ 5%</em></article><article><small>Quá hạn</small><b>18</b><em class="red">↓ 3%</em></article></div>
        <div class="ld-content"><section><h4>Task gần đây</h4>@foreach([['Thiết kế giao diện trang chủ','Web Redesign','Đang tiến hành','20/05/2024'],['Họp với khách hàng','Dự án Website','Hoàn thành','19/05/2024'],['Viết tài liệu hướng dẫn','Marketing Campaign','Đang tiến hành','18/05/2024'],['Kiểm tra và fix bug','Mobile App','Quá hạn','17/05/2024']] as $row)<div class="ld-task"><i>✓</i><span><b>{{ $row[0] }}</b><small>{{ $row[1] }}</small></span><em>{{ $row[2] }}</em><time>{{ $row[3] }}</time></div>@endforeach</section><aside><section><h4>Tiến độ công việc</h4><div class="mock-ring">75%<small>Hoàn thành</small></div></section><section><h4>Lịch sắp tới</h4><p>▣　Họp team weekly<br><small>　　Hôm nay, 10:00 AM</small></p><p>▣　Demo sản phẩm<br><small>　　Thứ 6, 24/05</small></p></section></aside></div></main>
    </div>
    <blockquote>❝　“Kế hoạch không có hành động chỉ là ước mơ.”<small>— Antoine de Saint-Exupéry</small></blockquote><div class="visual-waves"></div>
</section></div></body></html>
