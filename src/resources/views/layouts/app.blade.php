<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? '勤怠管理アプリ' }}</title>
    @php
        $hasViteHotFile = file_exists(public_path('hot'));
    @endphp

    @if ($hasViteHotFile)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
</head>
<body class="
    @if (
        request()->routeIs('attendance.*') ||
        request()->routeIs('admin.attendance.*') ||
        request()->routeIs('admin.staff.*') ||
        request()->routeIs('admin.requests.*') ||
        request()->routeIs('stamp.requests.*')
    ) && ! request()->routeIs('verification.notice')
    )
        body-app-pages
    @endif
    @if (request()->routeIs('admin.*'))
        body-admin-pages
    @endif
" @if (request()->is('email/verify')) style="background:#ffffff;" @endif>
    @php
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $isVerificationNotice = request()->routeIs('verification.notice');
        $navItems = [];

        if ($user && ! $isVerificationNotice && $isAdmin) {
            $navItems = [
                ['label' => '勤怠一覧', 'route' => route('admin.attendance.index'), 'active' => request()->routeIs('admin.attendance.*')],
                ['label' => 'スタッフ一覧', 'route' => route('admin.staff.index'), 'active' => request()->routeIs('admin.staff.*')],
                ['label' => '申請一覧', 'route' => route('stamp.requests.index'), 'active' => request()->routeIs('admin.requests.*') || request()->routeIs('stamp.requests.*')],
            ];
        } elseif ($user && ! $isVerificationNotice) {
            $navItems = [
                ['label' => '勤怠', 'route' => route('attendance.index'), 'active' => request()->routeIs('attendance.index')],
                ['label' => '勤怠一覧', 'route' => route('attendance.list'), 'active' => request()->routeIs('attendance.list') || request()->routeIs('attendance.show')],
                ['label' => '申請', 'route' => route('stamp.requests.index'), 'active' => request()->routeIs('attendance.requests.*') || request()->routeIs('stamp.requests.*')],
            ];
        }
    @endphp

    <header class="site-header">
        <div class="site-header-inner">
            <a class="site-logo" href="{{ $user ? ($isAdmin ? route('admin.attendance.index') : route('attendance.index')) : route('login') }}">
                <img src="{{ asset('images/coachtech-logo.png') }}" alt="COACHTECH">
            </a>

            @if ($user && ! $isVerificationNotice)
                <nav class="site-nav">
                    @foreach ($navItems as $item)
                        <a href="{{ $item['route'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach

                    <form method="POST" action="{{ $isAdmin ? route('admin.logout') : route('logout') }}">
                        @csrf
                        <button type="submit">ログアウト</button>
                    </form>
                </nav>
            @endif
        </div>
    </header>

    @yield('content')
</body>
</html>
