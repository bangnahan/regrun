<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Jelatix - Platform Tiketing & Registrasi Event Lari Indonesia' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Jelatix adalah platform penjualan tiket dan manajemen registrasi resmi event lari (Marathon, Half Marathon, 10K, 5K Fun Run) di Indonesia dengan pembayaran instan Tripay.' }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <!-- Plus Jakarta Sans Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col antialiased selection:bg-orange-500 selection:text-white" x-data="{ mobileOpen: false }">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-orange-600 via-amber-600 to-orange-700 text-white text-xs font-semibold py-2 px-4 text-center tracking-wide">
        <span>🏃 Platform Tiketing Resmi Event Lari Indonesia &bull; Pembayaran Otomatis & Terverifikasi via Tripay</span>
    </div>

    <!-- Navigation Header -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('register.index') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-orange-50 border border-orange-200/60 p-1.5 flex items-center justify-center shadow-xs transition transform group-hover:scale-105">
                    <img src="{{ asset('images/jelatix-icon.png') }}" alt="Jelatix Logo" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="font-black text-2xl text-slate-950 tracking-tight leading-tight">Jelatix</span>
                    <span class="text-[10px] font-bold text-orange-600 uppercase tracking-widest">Running Ticketing</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-slate-600">
                <a href="{{ route('register.index') }}" class="hover:text-orange-600 transition {{ request()->routeIs('register.index') ? 'text-orange-600 font-bold' : '' }}">
                    Beranda
                </a>
                <a href="{{ route('register.index') }}#events" class="hover:text-orange-600 transition">
                    Event Lari
                </a>
                <a href="{{ route('page.terms') }}" class="hover:text-orange-600 transition {{ request()->routeIs('page.terms') ? 'text-orange-600 font-bold' : '' }}">
                    Syarat & Ketentuan
                </a>
                <a href="{{ route('page.refund') }}" class="hover:text-orange-600 transition {{ request()->routeIs('page.refund') ? 'text-orange-600 font-bold' : '' }}">
                    Refund Policy
                </a>
                <a href="{{ route('page.privacy') }}" class="hover:text-orange-600 transition {{ request()->routeIs('page.privacy') ? 'text-orange-600 font-bold' : '' }}">
                    Privasi
                </a>
                <a href="{{ route('page.contact') }}" class="hover:text-orange-600 transition {{ request()->routeIs('page.contact') ? 'text-orange-600 font-bold' : '' }}">
                    Kontak
                </a>
            </nav>

            <!-- CTA Button -->
            <div class="hidden sm:flex items-center gap-3">
                <a href="{{ route('register.index') }}#events" class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs shadow-md shadow-orange-600/20 transition flex items-center gap-2">
                    <span>Cari Tiket Event</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <!-- Mobile Hamburger Toggle -->
            <div class="flex items-center md:hidden">
                <button type="button" @click="mobileOpen = !mobileOpen" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div x-show="mobileOpen" x-transition class="md:hidden border-b border-slate-200 bg-white px-4 pt-2 pb-6 space-y-2 text-sm font-semibold text-slate-700">
            <a href="{{ route('register.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-50">Beranda</a>
            <a href="{{ route('register.index') }}#events" class="block px-3 py-2 rounded-lg hover:bg-slate-50">Daftar Event Lari</a>
            <a href="{{ route('page.terms') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-50">Syarat & Ketentuan</a>
            <a href="{{ route('page.refund') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-50">Refund Policy</a>
            <a href="{{ route('page.privacy') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-50">Kebijakan Privasi</a>
            <a href="{{ route('page.contact') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-50">Kontak Kami</a>
            <div class="pt-3">
                <a href="{{ route('register.index') }}#events" class="block text-center w-full px-4 py-2.5 rounded-xl bg-orange-600 text-white font-bold text-xs shadow">
                    Cari Tiket Event Lari &rarr;
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Global Footer (Tripay & Merchant Compliance) -->
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-slate-800">
                <!-- Col 1: Brand Info -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white p-1.5 flex items-center justify-center shadow-md">
                            <img src="{{ asset('images/jelatix-icon.png') }}" alt="Jelatix Logo" class="w-full h-full object-contain">
                        </div>
                        <span class="font-extrabold text-2xl text-white tracking-tight">Jelatix</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                        <strong>Jelatix Ticketing</strong> adalah platform teknologi layanan pemesanan tiket resmi dan manajemen pendaftaran event lari di Indonesia. Membantu pelari mendapatkan slot lomba secara instan dan aman, serta menyediakan solusi menyeluruh bagi race organizer.
                    </p>
                    <div class="flex items-center gap-2 pt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-950/80 border border-emerald-800 text-emerald-400 text-[11px] font-bold">
                            <span>🛡️</span> Pembayaran Terenkripsi 256-Bit
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-950/80 border border-orange-800 text-orange-400 text-[11px] font-bold">
                            <span>⚡</span> E-Ticket QR Instan
                        </span>
                    </div>
                </div>

                <!-- Col 2: Navigasi Platform -->
                <div class="space-y-3">
                    <h4 class="text-white text-xs font-black uppercase tracking-wider">Jelajahi</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('register.index') }}" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="{{ route('register.index') }}#events" class="hover:text-white transition">Event Lari Aktif</a></li>
                        <li><a href="{{ route('register.index') }}#features" class="hover:text-white transition">Fitur Platform</a></li>
                        <li><a href="{{ route('register.index') }}#organizer" class="hover:text-white transition">Untuk Organizer</a></li>
                    </ul>
                </div>

                <!-- Col 3: Kepatuhan & Kebijakan Hukum (Wajib Tripay) -->
                <div class="space-y-3">
                    <h4 class="text-white text-xs font-black uppercase tracking-wider">Kepatuhan & Legal</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('page.terms') }}" class="hover:text-white transition">Syarat & Ketentuan</a></li>
                        <li><a href="{{ route('page.refund') }}" class="hover:text-white transition">Kebijakan Pengembalian (Refund)</a></li>
                        <li><a href="{{ route('page.privacy') }}" class="hover:text-white transition">Kebijakan Privasi</a></li>
                        <li><a href="{{ route('page.contact') }}" class="hover:text-white transition">Layanan Pengaduan</a></li>
                    </ul>
                </div>

                <!-- Col 4: Layanan Pelanggan -->
                <div class="space-y-3">
                    <h4 class="text-white text-xs font-black uppercase tracking-wider">Hubungi Kami</h4>
                    <div class="space-y-2 text-xs text-slate-400">
                        <p class="flex items-start gap-2">
                            <span class="text-slate-200">✉️</span>
                            <span>Email: <a href="mailto:hi@jelatix.com" class="text-orange-400 hover:underline">hi@jelatix.com</a></span>
                        </p>
                        <p class="flex items-start gap-2">
                            <span class="text-slate-200">💬</span>
                            <span>WhatsApp: <a href="https://wa.me/6281916444458" target="_blank" class="text-orange-400 hover:underline">0819-1644-4458</a></span>
                        </p>
                        <p class="flex items-start gap-2">
                            <span class="text-slate-200">🏢</span>
                            <span>Kota Pekanbaru, Riau, Indonesia</span>
                        </p>
                        <p class="text-[11px] text-slate-500 pt-1">
                            Jam Kerja: Senin - Jumat (09:00 - 17:00 WIB)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payment Partners & Badges -->
            <div class="py-8 border-b border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <div>
                    <div class="text-slate-400 font-semibold mb-2">Didukung oleh Saluran Pembayaran Resmi:</div>
                    <div class="flex items-center flex-wrap gap-2 text-[11px] text-slate-300">
                        <span class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700">QRIS (GoPay, OVO, ShopeePay, DANA)</span>
                        <span class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700">BCA Virtual Account</span>
                        <span class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700">BRI Virtual Account</span>
                        <span class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700">Mandiri Virtual Account</span>
                        <span class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700">BNI Virtual Account</span>
                    </div>
                </div>
                <div class="text-right sm:text-right text-slate-500 text-[11px]">
                    <div class="font-bold text-slate-400">Payment Gateway Partner</div>
                    <div>Tripay Payment Gateway (Lisensi Bank Indonesia)</div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} Jelatix Ticketing (jelatix.com). Seluruh hak cipta dilindungi undang-undang.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('page.terms') }}" class="hover:text-slate-400 transition">Syarat & Ketentuan</a>
                    <span>&bull;</span>
                    <a href="{{ route('page.privacy') }}" class="hover:text-slate-400 transition">Privasi</a>
                    <span>&bull;</span>
                    <a href="{{ route('page.refund') }}" class="hover:text-slate-400 transition">Refund</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
