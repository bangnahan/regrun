<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Panel Admin Event Lari' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col md:flex-row">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-slate-900 text-slate-300 flex-shrink-0 flex flex-col border-r border-slate-800">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white p-1.5 flex items-center justify-center shadow-md">
                    <img src="{{ asset('images/jelatix-icon.png') }}" alt="Jelatix" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-white font-bold text-base leading-tight">Jelatix Admin</h1>
                    <p class="text-xs text-slate-400">Event Ticketing Platform</p>
                </div>
            </div>
        </div>

        <nav class="p-4 flex-1 space-y-1.5 text-sm font-medium">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-orange-600 text-white font-semibold shadow' : 'hover:bg-slate-800 text-slate-300' }}">
                <span>📊</span> Dashboard
            </a>

            <!-- Highlighted: Rekap Jersey Pabrik -->
            <a href="{{ route('admin.jersey_recap') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.jersey_recap*') ? 'bg-orange-600 text-white font-semibold shadow' : 'hover:bg-slate-800 text-amber-300 bg-slate-800/60' }}">
                <div class="flex items-center gap-3">
                    <span>🎽</span> Rekap Jersey Pabrik
                </div>
                <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-amber-400/20 text-amber-300 border border-amber-400/30">Pabrik</span>
            </a>

            <a href="{{ route('admin.transactions') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.transactions*') ? 'bg-orange-600 text-white font-semibold shadow' : 'hover:bg-slate-800 text-slate-300' }}">
                <span>💳</span> Transaksi Tiket
            </a>

            <a href="{{ route('admin.participants') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.participants*') ? 'bg-orange-600 text-white font-semibold shadow' : 'hover:bg-slate-800 text-slate-300' }}">
                <span>🏃</span> Data Pelari (BIB)
            </a>

            <a href="{{ route('admin.events') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.events*') ? 'bg-orange-600 text-white font-semibold shadow' : 'hover:bg-slate-800 text-slate-300' }}">
                <span>⚙️</span> Event &amp; Kuota Tiket
            </a>

            <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.settings*') ? 'bg-orange-600 text-white font-semibold shadow' : 'hover:bg-slate-800 text-slate-300' }}">
                <span>🔌</span> Tripay &amp; Mailketing
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold text-white">{{ Auth::user()->name ?? 'Administrator' }}</div>
                    <div class="text-[11px] text-slate-400 truncate max-w-[120px]">{{ Auth::user()->email ?? 'admin' }}</div>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 px-2 py-1 rounded bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 transition">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="bg-white border-b border-slate-200 h-16 px-6 flex items-center justify-between sticky top-0 z-20 shadow-sm">
            <div class="font-bold text-slate-800 text-base">
                @yield('page_title', 'Admin Dashboard')
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('register.index') }}" target="_blank" class="text-xs font-medium text-slate-600 hover:text-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 flex items-center gap-1.5 transition">
                    <span>Lihat Web Registrasi</span> ↗
                </a>
            </div>
        </header>

        <!-- Body -->
        <main class="flex-1 p-6 max-w-7xl w-full mx-auto">
            <!-- Flash Alert -->
            @if(session('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-sm">
                    <span class="text-emerald-500 font-bold text-lg">&check;</span>
                    <div class="flex-1 font-medium">{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-sm">
                    <span class="text-rose-500 font-bold text-lg">&times;</span>
                    <div class="flex-1 font-medium">{{ session('error') }}</div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
